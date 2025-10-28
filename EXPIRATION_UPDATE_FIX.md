# Authorization Template Expiration Date Update Fix

## Problem Description

When an Authorization Template's expiration period was changed (e.g., from "No Expiration" to "60 days"), the expiration dates of existing endpoints associated with that template were not updated. This resulted in endpoints still showing "Never" in the Expiration Date column of the Managed iPSK Endpoints view, even though the template had been updated to have a specific expiration period.

## Root Cause

The system had functionality to update existing endpoints' PSKs and VLAN/dACL settings when an Authorization Template was modified, but there was no corresponding functionality to recalculate and update expiration dates based on the new term length setting.

## Solution Implemented

Added a new checkbox option in the Authorization Template edit form that allows administrators to recalculate and update expiration dates for all endpoints associated with the template.

### Changes Made

#### 1. UI Changes - `/supportfiles/adminportals/modules/authz/edit.inc.php`

- Added a new checkbox: **"Recalculate ALL Associated Endpoint's Expiration Dates"**
- Updated the warning message to include expiration dates
- Added the new checkbox value to the AJAX request data

#### 2. Backend Logic - `/supportfiles/adminportals/modules/authz/update.inc.php`

- Added logic to handle the new `fullAuthZUpdateExpiration` parameter
- When this option is selected, the system:
  - Retrieves all endpoints associated with the authorization template
  - Calculates the new expiration date based on the template's term length:
    - If term length is 0: Sets expiration to 0 (no expiration)
    - Otherwise: Sets expiration to current time + term length
  - Updates each endpoint's expiration date using the existing `extendEndpoint` function
  - Also resets the `accountExpired` flag to 'False' for each endpoint

## How to Use

1. Navigate to **Authorization Templates** in the admin portal
2. Click the **Edit** icon for the template you want to modify
3. Change the **Access Term Length** to the desired duration
4. Check the box: **"Recalculate ALL Associated Endpoint's Expiration Dates"**
5. Click **Update**

All endpoints associated with this template will now have their expiration dates recalculated based on the current time plus the new term length.

## Important Notes

- This is an **opt-in feature** - you must check the checkbox for expiration dates to be updated
- The expiration date is calculated from the **current time** when you click Update, not from the original endpoint creation date
- For endpoints that previously had "No Expiration" (0), they will now have a calculated expiration date
- For endpoints that already had an expiration date, it will be overwritten with the new calculated date
- The `accountExpired` flag is reset to 'False' for all updated endpoints

## Testing Recommendations

1. Create a test Authorization Template with "No Expiration"
2. Create some test endpoints using this template
3. Verify endpoints show "Never" in the Expiration Date column
4. Edit the Authorization Template and change to a specific term length (e.g., "1 Week")
5. Check the "Recalculate ALL Associated Endpoint's Expiration Dates" box
6. Click Update
7. Verify that the endpoints now show the calculated expiration date in the Managed iPSK Endpoints view

## Related Files

- `/supportfiles/adminportals/modules/authz/edit.inc.php` - Authorization Template edit form
- `/supportfiles/adminportals/modules/authz/update.inc.php` - Authorization Template update logic
- `/supportfiles/include/iPSKManagerDatabase.php` - Database functions (uses existing `extendEndpoint` function)

