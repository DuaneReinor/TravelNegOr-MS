# Activity Logging Implementation - Travel NegOr

## 🎯 Overview

The Travel NegOr application now has a comprehensive activity logging system that tracks all user activities, from login/logout events to CRUD operations on entities. This provides administrators with complete audit trails for security and compliance purposes.

## 📋 What Gets Logged

### 1. **Security Events**
- ✅ **User Login** - Successful authentication
- ✅ **User Logout** - User session termination  
- ✅ **Failed Login Attempts** - Invalid credentials or unknown users
- 🔧 **Admin Panel Access** - Pages accessed in admin/staff areas

### 2. **Entity CRUD Operations**
- ✅ **CREATE** - New entities (Destination, Hotel, User)
- ✅ **UPDATE** - Entity modifications with old/new data comparison
- ✅ **DELETE** - Entity removal

### 3. **Data Captured for Each Event**
- **User Information** - Who performed the action
- **IP Address** - Geographic location of the activity
- **User Agent** - Browser/device information
- **Timestamps** - Precise activity timing
- **Entity Details** - What was affected (ID, name, type)
- **Change History** - Before/after data for updates

## 🏗️ Architecture

### Core Components

1. **ActivityLog Entity** (`src/Entity/ActivityLog.php`)
   - Comprehensive data model for all activity records
   - JSON fields for flexible data storage
   - Foreign key relationships to users

2. **ActivityLogSubscriber** (`src/EventSubscriber/ActivityLogSubscriber.php`)
   - Doctrine event subscriber for automatic CRUD logging
   - Listens to: `postPersist`, `preUpdate`, `postUpdate`, `postRemove`
   - Captures entity changes with full context

3. **SecurityActivitySubscriber** (`src/EventSubscriber/SecurityActivitySubscriber.php`)
   - Handles authentication events
   - Captures login/logout with SecurityEvents::INTERACTIVE_LOGIN
   - Tracks logout via KernelEvents::RESPONSE

4. **Admin Interface** (`src/Controller/AdminActivityLogController.php`)
   - Filtering by action type, entity, user
   - Search functionality across all fields
   - Pagination for large datasets
   - Statistics dashboard
   - Data cleanup tools

## 🎨 User Interface

### Activity Log Viewer
- **Admin Panel**: `/admin/activity-logs`
- **Filters**: Action type, entity type, search text
- **View Options**: List view, detailed view, entity-specific history
- **Export**: Clean old logs functionality

### Visual Indicators
- **🟢 LOGIN** - Successful authentication (Blue badge)
- **🔴 LOGOUT** - Session termination (Gray badge)  
- **⚠️ LOGIN_FAILED** - Failed attempts (Red badge with blink animation)
- **🟢 CREATE** - Entity creation (Green badge)
- **🔵 UPDATE** - Entity modification (Blue badge)
- **🔴 DELETE** - Entity removal (Red badge)

## 🚀 How to Test

### 1. **Run Comprehensive Test**
```bash
php bin/console app:comprehensive-activity-test
```
This command creates sample activities across all event types.

### 2. **Manual Testing**

#### Test Login/Logout:
1. Login with any user account
2. Check Activity Logs - should see LOGIN event
3. Logout - should see LOGOUT event
4. Try invalid credentials - should see LOGIN_FAILED event

#### Test CRUD Operations:
1. Create/edit/delete destinations
2. Create/edit/delete hotels  
3. Modify user accounts
4. All actions should appear in activity logs

### 3. **View in Admin Panel**
1. Login as admin: `admin@travelnegor.com` / `admin123`
2. Navigate to **Admin Panel → Activity Logs**
3. Use filters to view specific activity types
4. Click "View" on any log entry for details

## 📊 Database Schema

### Activity Logs Table
```sql
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(255) NOT NULL,           -- LOGIN, LOGOUT, CREATE, UPDATE, DELETE
    entity_type VARCHAR(255) NOT NULL,      -- User, Destination, Hotel, AdminPanel
    entity_id INT DEFAULT NULL,             -- ID of affected entity
    entity_name VARCHAR(255) DEFAULT NULL,  -- Name/title of entity
    user_id INT DEFAULT NULL,               -- FK to user table
    user_email VARCHAR(255) DEFAULT NULL,   -- Denormalized email for performance
    description LONGTEXT,                   -- Human-readable description
    old_data LONGTEXT,                      -- JSON: previous state (for updates)
    new_data LONGTEXT,                      -- JSON: new state (for creates/updates)
    ip_address VARCHAR(255),                -- Client IP address
    user_agent VARCHAR(255),                -- Browser/client info
    created_at DATETIME NOT NULL,           -- When the activity occurred
    INDEX idx_activity_logs_action,         -- Performance indexes
    INDEX idx_activity_logs_entity,
    INDEX idx_activity_logs_user,
    INDEX idx_activity_logs_created_at
);
```

## 🔧 Configuration

### Services (automatically registered via attributes)
- `ActivityLogSubscriber` - Handles entity events
- `SecurityActivitySubscriber` - Handles authentication events

### No additional configuration required!

## 📈 Benefits

### For Administrators:
- **Complete Audit Trail** - Know who did what, when, and from where
- **Security Monitoring** - Track failed login attempts and suspicious activity
- **Compliance** - Meet regulatory requirements for data access logging
- **Troubleshooting** - Debug issues by reviewing user actions

### For Development:
- **Debugging** - Track data changes during development
- **Testing** - Verify that actions are properly logged
- **Performance** - Monitor system usage patterns

### For Business:
- **Accountability** - Users are accountable for their actions
- **Transparency** - Clear record of all system interactions
- **Risk Management** - Early detection of unauthorized access attempts

## 🛠️ Maintenance

### Data Cleanup
- Use the **Clean Old Logs** function in admin panel
- Automatically removes logs older than specified days (default: 90)
- Prevents database browth from extensive logging

### Monitoring
- Check activity logs regularly for:
  - Multiple failed login attempts (potential brute force)
  - Unusual access patterns
  - After-hours administrative activities

## 🔍 Troubleshooting

### No Logs Appearing?
1. Verify subscribers are registered: `php bin/console debug:event-dispatcher`
2. Check database connection and table creation
3. Ensure user has proper roles for admin panel access
4. Test with the comprehensive test command

### Performance Impact?
- Logs are written synchronously during development
- Consider async logging for high-traffic production environments
- Regular cleanup prevents database growth issues

## 🎉 Success Criteria

✅ **Login/Logout Tracking** - All authentication events captured  
✅ **CRUD Operation Logging** - All entity changes automatically tracked  
✅ **Admin Interface** - Complete activity log viewer with filtering  
✅ **Data Integrity** - Full audit trail with context and timestamps  
✅ **User Experience** - Clean, intuitive interface for administrators  
✅ **Security Monitoring** - Track failed attempts and suspicious activity  

The activity logging system is now fully operational and provides comprehensive monitoring of all user activities in the Travel NegOr application!