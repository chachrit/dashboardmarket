-- ============================================================================
-- Dashboard Market - MS SQL Server Database Setup
-- สำหรับการใช้งานกับ Microsoft SQL Server
-- ============================================================================

-- 1. สร้างฐานข้อมูล (ถ้ายังไม่มี)
-- !!!! เปลี่ยน 'dashboardmarket' เป็นชื่อที่คุณต้องการ !!!!
-- USE master;
-- GO
-- CREATE DATABASE dashboardmarket
-- ON (NAME = 'dashboardmarket', FILENAME = 'C:\Data\dashboardmarket.mdf')
-- LOG ON (NAME = 'dashboardmarket_log', FILENAME = 'C:\Data\dashboardmarket_log.ldf');
-- GO

-- เปลี่ยนไปใช้ฐานข้อมูลที่สร้าง
USE dashboardmarket;
GO

-- ============================================================================
-- 2. สร้างตาราง dm_settings (สำหรับการตั้งค่า)
-- ============================================================================
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[dm_settings]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[dm_settings] (
        [scope] NVARCHAR(50) NOT NULL,
        [name] NVARCHAR(100) NOT NULL,
        [value] NVARCHAR(MAX) NULL,
        [updated_at] BIGINT NULL,
        CONSTRAINT [PK_dm_settings] PRIMARY KEY CLUSTERED ([scope] ASC, [name] ASC)
    );
    
    PRINT '✅ Table dm_settings created successfully';
END
ELSE
BEGIN
    PRINT '✅ Table dm_settings already exists';
END
GO

-- ============================================================================
-- 3. สร้างตาราง orders (สำหรับเก็บข้อมูล orders)
-- ============================================================================
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[orders]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[orders] (
        [id] BIGINT IDENTITY(1,1) NOT NULL,
        [platform] NVARCHAR(20) NOT NULL,
        [order_id] NVARCHAR(100) NOT NULL,
        [amount] DECIMAL(15,2) NULL DEFAULT 0,
        [status] NVARCHAR(50) NULL,
        [created_at] DATETIME2(7) NULL,
        [items] NVARCHAR(MAX) NULL, -- JSON data
        [raw_data] NVARCHAR(MAX) NULL, -- JSON data
        [fetched_at] BIGINT NULL, -- Unix timestamp
        CONSTRAINT [PK_orders] PRIMARY KEY CLUSTERED ([id] ASC),
        CONSTRAINT [UQ_orders_platform_order_id] UNIQUE NONCLUSTERED ([platform] ASC, [order_id] ASC)
    );
    
    PRINT '✅ Table orders created successfully';
END
ELSE
BEGIN
    PRINT '✅ Table orders already exists';
END
GO

-- ============================================================================
-- 4. สร้าง Index เพื่อเพิ่มประสิทธิภาพ
-- ============================================================================

-- Index สำหรับค้นหาตาม platform และ created_at
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_orders_platform_created_at')
BEGIN
    CREATE NONCLUSTERED INDEX [IX_orders_platform_created_at] 
    ON [dbo].[orders] ([platform] ASC, [created_at] DESC)
    INCLUDE ([amount], [status], [fetched_at]);
    
    PRINT '✅ Index IX_orders_platform_created_at created successfully';
END
ELSE
BEGIN
    PRINT '✅ Index IX_orders_platform_created_at already exists';
END
GO

-- Index สำหรับค้นหา fetched_at (สำหรับ cache freshness check)
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_orders_fetched_at')
BEGIN
    CREATE NONCLUSTERED INDEX [IX_orders_fetched_at] 
    ON [dbo].[orders] ([fetched_at] DESC)
    INCLUDE ([platform]);
    
    PRINT '✅ Index IX_orders_fetched_at created successfully';
END
ELSE
BEGIN
    PRINT '✅ Index IX_orders_fetched_at already exists';
END
GO

-- ============================================================================
-- 5. สร้าง Stored Procedures สำหรับการใช้งาน
-- ============================================================================

-- Stored Procedure สำหรับดึงสถิติ
IF EXISTS (SELECT * FROM sys.objects WHERE type = 'P' AND name = 'sp_GetDashboardStats')
    DROP PROCEDURE [dbo].[sp_GetDashboardStats];
GO

CREATE PROCEDURE [dbo].[sp_GetDashboardStats]
    @Platform NVARCHAR(20),
    @DateFrom DATE = NULL,
    @DateTo DATE = NULL
AS
BEGIN
    SET NOCOUNT ON;
    
    -- Set default dates if not provided
    IF @DateFrom IS NULL SET @DateFrom = CAST(GETDATE() AS DATE);
    IF @DateTo IS NULL SET @DateTo = CAST(GETDATE() AS DATE);
    
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(amount), 0) as total_sales,
        MAX(fetched_at) as last_fetch_time
    FROM [dbo].[orders] 
    WHERE platform = @Platform
      AND created_at >= @DateFrom
      AND created_at < DATEADD(DAY, 1, @DateTo);
END
GO

PRINT '✅ Stored Procedure sp_GetDashboardStats created successfully';

-- Stored Procedure สำหรับตรวจสอบ cache freshness
IF EXISTS (SELECT * FROM sys.objects WHERE type = 'P' AND name = 'sp_CheckCacheFreshness')
    DROP PROCEDURE [dbo].[sp_CheckCacheFreshness];
GO

CREATE PROCEDURE [dbo].[sp_CheckCacheFreshness]
    @Platform NVARCHAR(20),
    @MaxAgeMinutes INT = 30
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @LastFetch BIGINT;
    DECLARE @Now BIGINT = DATEDIFF(SECOND, '1970-01-01', GETUTCDATE());
    DECLARE @IsFresh BIT = 0;
    
    SELECT @LastFetch = MAX(fetched_at) 
    FROM [dbo].[orders] 
    WHERE platform = @Platform;
    
    IF @LastFetch IS NOT NULL
    BEGIN
        DECLARE @AgeMinutes FLOAT = (@Now - @LastFetch) / 60.0;
        IF @AgeMinutes <= @MaxAgeMinutes
            SET @IsFresh = 1;
    END
    
    SELECT @IsFresh as is_fresh, @LastFetch as last_fetch, @Now as current_time;
END
GO

PRINT '✅ Stored Procedure sp_CheckCacheFreshness created successfully';

-- ============================================================================
-- 6. สร้าง View สำหรับ Recent Orders
-- ============================================================================
IF EXISTS (SELECT * FROM sys.views WHERE name = 'vw_RecentOrders')
    DROP VIEW [dbo].[vw_RecentOrders];
GO

CREATE VIEW [dbo].[vw_RecentOrders]
AS
SELECT TOP 50
    platform,
    order_id,
    amount,
    status,
    created_at,
    items,
    ROW_NUMBER() OVER (PARTITION BY platform ORDER BY created_at DESC) as rn
FROM [dbo].[orders]
WHERE created_at >= DATEADD(DAY, -7, GETDATE()) -- Only last 7 days
GO

PRINT '✅ View vw_RecentOrders created successfully';

-- ============================================================================
-- 7. การทำความสะอาดข้อมูลเก่า (Optional)
-- ============================================================================

-- สร้าง Stored Procedure สำหรับลบข้อมูลเก่า
IF EXISTS (SELECT * FROM sys.objects WHERE type = 'P' AND name = 'sp_CleanupOldOrders')
    DROP PROCEDURE [dbo].[sp_CleanupOldOrders];
GO

CREATE PROCEDURE [dbo].[sp_CleanupOldOrders]
    @DaysToKeep INT = 90
AS
BEGIN
    SET NOCOUNT ON;
    
    DECLARE @CutoffDate DATETIME2 = DATEADD(DAY, -@DaysToKeep, GETDATE());
    DECLARE @RowsDeleted INT;
    
    DELETE FROM [dbo].[orders] 
    WHERE created_at < @CutoffDate;
    
    SET @RowsDeleted = @@ROWCOUNT;
    
    PRINT CONCAT('🗑️ Cleaned up ', @RowsDeleted, ' old orders (older than ', @DaysToKeep, ' days)');
    
    -- Update statistics after cleanup
    UPDATE STATISTICS [dbo].[orders];
END
GO

PRINT '✅ Stored Procedure sp_CleanupOldOrders created successfully';

-- ============================================================================
-- 8. ข้อมูล Configuration ตัวอย่าง
-- ============================================================================

-- Insert default settings (ถ้ายังไม่มี)
IF NOT EXISTS (SELECT * FROM [dbo].[dm_settings] WHERE scope = 'shopee' AND name = 'enabled')
BEGIN
    INSERT INTO [dbo].[dm_settings] (scope, name, value, updated_at) VALUES
    ('shopee', 'enabled', 'false', DATEDIFF(SECOND, '1970-01-01', GETUTCDATE())),
    ('lazada', 'enabled', 'false', DATEDIFF(SECOND, '1970-01-01', GETUTCDATE())),
    ('tiktok', 'enabled', 'false', DATEDIFF(SECOND, '1970-01-01', GETUTCDATE()));
    
    PRINT '✅ Default platform settings inserted';
END
ELSE
BEGIN
    PRINT '✅ Platform settings already exist';
END

-- ============================================================================
-- 9. ตรวจสอบความสมบูรณ์ของการติดตั้ง
-- ============================================================================
PRINT '============================================================================';
PRINT '📊 Dashboard Market - MS SQL Server Setup Complete!';
PRINT '============================================================================';
PRINT 'Tables created:';
PRINT '  ✅ dm_settings (configuration storage)';
PRINT '  ✅ orders (orders data with indexes)';
PRINT 'Stored Procedures:';
PRINT '  ✅ sp_GetDashboardStats';
PRINT '  ✅ sp_CheckCacheFreshness';
PRINT '  ✅ sp_CleanupOldOrders';
PRINT 'Views:';
PRINT '  ✅ vw_RecentOrders';
PRINT '';
PRINT '🔧 Next Steps:';
PRINT '1. Update your config.php with MS SQL Server connection settings';
PRINT '2. Test connection using: php debug_api.php';
PRINT '3. Configure API credentials in settings.php';
PRINT '4. Run first data fetch: php fetch_orders.php all';
PRINT '============================================================================';

-- แสดงจำนวนข้อมูลปัจจุบัน
SELECT 
    'Current Data Status' as Info,
    (SELECT COUNT(*) FROM [dbo].[dm_settings]) as Settings_Count,
    (SELECT COUNT(*) FROM [dbo].[orders]) as Orders_Count,
    (SELECT COUNT(DISTINCT platform) FROM [dbo].[orders]) as Platforms_Count;
