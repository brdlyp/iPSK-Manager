# Bulk Endpoint Group Change Feature

## Overview
This feature allows administrators to select multiple endpoints from the "Managed iPSK Endpoints" view and change their endpoint group in bulk, streamlining the management of large numbers of devices.

## Branch
- **Branch Name**: `feature/bulk-endpoint-group-change`
- **Status**: Implementation Complete

## Files Modified

### 1. `/supportfiles/include/iPSKManagerFunctions.php`
**Changes:**
- Updated `$subModuleRegEx` to include `bulkgroupchange` and `bulkgroupupdate` in the whitelist of allowed sub-modules
- Added `selectedEndpointIds` parameter to the input sanitization array to validate comma-separated endpoint IDs

### 2. `/supportfiles/adminportals/modules/endpoints/endpoints.inc.php`
**Changes:**
- Added checkbox column to the endpoints table for selecting multiple endpoints
- Added "Select All" checkbox in the table header (no filter input field in checkbox column)
- Added "Bulk Group Change" button (initially hidden, shows when endpoints are selected)
- Added badge showing count of selected endpoints
- Implemented JavaScript handlers for:
  - Individual checkbox selection with persistent storage
  - Select All functionality
  - Showing/hiding bulk action button based on selection
  - Updating selected count badge
  - **Persistent selection across pagination** - selections are maintained when moving between pages
  - Automatic restoration of checkbox states after table redraws (sort, filter, pagination)
- Updated DataTables column definitions to accommodate the new checkbox column
- Added `drawCallback` to restore checkbox states after table operations

### 3. `/supportfiles/adminportals/modules/endpoints/bulkgroupchange.inc.php` (NEW)
**Purpose:** Modal dialog for bulk endpoint group changes

**Features:**
- Displays a dropdown of available endpoint groups
- Shows endpoint group details (maximum access duration, PSK type)
- Lists all selected endpoints with their MAC addresses (from persistent storage)
- Shows total count of selected endpoints across all pages
- Shows warning about the implications of the bulk change
- Includes confirmation dialog before proceeding
- Validates that an endpoint group is selected
- Passes selected endpoint IDs to the processing module

### 4. `/supportfiles/adminportals/modules/endpoints/bulkgroupupdate.inc.php` (NEW)
**Purpose:** Backend processing for bulk endpoint group changes

**Features:**
- Processes multiple endpoints in a single operation
- For each endpoint:
  - Retrieves endpoint details by association ID
  - Gets new endpoint group authorization settings
  - Generates new PSK based on group type (device random, user random, or common)
  - Calculates new expiration date based on group term length
  - Updates endpoint in ISE with new settings
  - Deletes old endpoint association
  - Creates new endpoint association with new group
  - Logs success or failure
- Displays detailed results modal showing:
  - Summary statistics (total processed, successful, failed)
  - Detailed per-endpoint results table
  - Color-coded status indicators
- Automatically refreshes the endpoints view after completion
- Clears selections after successful bulk update
- Comprehensive logging for audit trail

## How to Use

### Step 1: Select Endpoints
1. Navigate to the "Managed iPSK Endpoints" view
2. Check the checkbox next to each endpoint you want to update
   - OR use the "Select All" checkbox in the header to select all visible endpoints
   - **Selections persist across pages** - you can navigate through pagination and select endpoints on different pages
3. A badge will show the total number of selected endpoints (across all pages)
4. The "Bulk Group Change" button will appear

### Step 2: Initiate Bulk Change
1. Click the "Bulk Group Change" button
2. A modal dialog will open showing:
   - Dropdown to select the new endpoint group
   - Group details (expiration, PSK type)
   - List of selected endpoints
   - Warning about the action

### Step 3: Select New Group and Confirm
1. Select the desired endpoint group from the dropdown
2. Review the selected endpoints list
3. Read the warning message carefully
4. Click "Update Endpoint Groups"
5. Confirm the action in the confirmation dialog

### Step 4: Review Results
1. A results modal will display showing:
   - Summary of successful and failed updates
   - Detailed table with status for each endpoint
2. Click "Return to Endpoints" or close the modal
3. The endpoints view will refresh automatically

## Technical Details

### PSK Handling
The system correctly handles different PSK types based on the new endpoint group:
- **Device Random**: Generates a new random PSK for each device
- **User Random**: Uses existing user PSK or generates new one if not found
- **Common PSK**: Uses the group's common PSK

### Expiration Date Handling
- If new group has no expiration (termLengthSeconds = 0): Sets to never expire
- If new group has expiration: Calculates from current time + term length

### Database Operations
For each endpoint, the system:
1. Calls `getEndpointByAssociationId()` to get endpoint details
2. Calls `getAuthorizationTemplatesbyEPGroupId()` to get new group settings
3. Calls `updateEndpoint()` to update the endpoint in ISE
4. Calls `deleteEndpointAssociationbyId()` to remove old association
5. Calls `addEndpointAssociation()` to create new association

### Logging
All operations are logged with:
- Action type: BULKGROUPCHANGE
- MAC address
- New group name
- Username and session ID
- Remote IP address
- Success/failure status
- Summary statistics

## Security Considerations

1. **Session Validation**: Uses existing session management (`$_SESSION['logonSID']`)
2. **Input Sanitization**: Relies on existing `$sanitizedInput` mechanism
3. **Confirmation Required**: Users must confirm before executing bulk changes
4. **Audit Trail**: All changes are logged for compliance
5. **Transaction Safety**: Each endpoint is updated individually; partial success is possible

## Testing Checklist

### Basic Functionality
- [ ] Checkboxes appear in the endpoints table
- [ ] Select All checkbox works correctly
- [ ] Individual checkboxes can be selected/deselected
- [ ] Selected count badge updates correctly
- [ ] Bulk Group Change button appears when endpoints are selected
- [ ] Bulk Group Change button hides when no endpoints are selected

### Modal Dialog
- [ ] Modal opens when Bulk Group Change button is clicked
- [ ] Selected endpoints are listed correctly
- [ ] Endpoint groups dropdown populates
- [ ] Group details update when selection changes
- [ ] Update button enables when group is selected
- [ ] Confirmation dialog appears before processing

### Bulk Update Processing
- [ ] Single endpoint update works correctly
- [ ] Multiple endpoint updates work correctly
- [ ] PSK generation works for device random type
- [ ] PSK generation works for user random type
- [ ] PSK generation works for common PSK type
- [ ] Expiration dates are calculated correctly
- [ ] Results modal shows correct summary
- [ ] Detailed results table shows all endpoints
- [ ] Success/failure indicators are correct
- [ ] Endpoints view refreshes after completion

### Edge Cases
- [ ] Handles endpoints with different original groups
- [ ] Handles invalid/non-existent endpoint IDs gracefully
- [ ] Works correctly with DataTables filtering/sorting
- [ ] Works correctly with DataTables pagination
- [ ] Handles zero endpoints selected
- [ ] Handles large numbers of endpoints (100+)

### Logging
- [ ] Success operations are logged correctly
- [ ] Failure operations are logged correctly
- [ ] Summary statistics are logged
- [ ] Log entries include all required fields

## Future Enhancements (Optional)

1. **Progress Indicator**: Show real-time progress during bulk updates
2. **Undo Functionality**: Option to revert bulk changes
3. **Scheduled Changes**: Schedule bulk changes for a future time
4. **Email Notifications**: Notify affected users of the change
5. **Export Results**: Export results to CSV or PDF
6. **Dry Run Mode**: Preview changes before applying
7. **Filtering**: Apply bulk change only to filtered endpoints

## Support Information

For issues or questions:
- Check application logs for detailed error messages
- Review ISE connectivity if endpoint updates fail
- Verify database permissions for association changes
- Ensure proper user permissions for bulk operations

---

**Created**: October 28, 2025  
**Author**: AI Assistant  
**Version**: 1.0

