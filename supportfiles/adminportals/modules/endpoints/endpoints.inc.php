<?php
	
/**
 *@license
 *
 *Copyright 2021 Cisco Systems, Inc. or its affiliates
 *
 *Licensed under the Apache License, Version 2.0 (the "License");
 *you may not use this file except in compliance with the License.
 *You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *Unless required by applicable law or agreed to in writing, software
 *distributed under the License is distributed on an "AS IS" BASIS,
 *WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *See the License for the specific language governing permissions and
 *limitations under the License.
 */
	
	$actionRowData = "";
	$pageData['endpointAssociationList'] = '';
	
	$associationList = $ipskISEDB->getEndPointAssociations();
	$pageStart = 0;
	$pageEnd = (isset($associationList['count'])) ? $associationList['count'] : 0;
		
	if($associationList){
		if($associationList['count'] > 0){

			$pageData['endpointAssociationList'] .= '<table id="endpoint-table" class="table table-hover"><thead><tr id="endpoint-table-filter"><th scope="col" data-dt-order="disable"><input type="checkbox" id="selectAll" title="Select All"></th><th scope="col" data-dt-order="disable">MAC Address</th><th scope="col" data-dt-order="disable">iPSK Endpoint Grouping</th><th scope="col" data-dt-order="disable">Expiration Date</th><th scope="col" data-dt-order="disable">Full Name</th><th scope="col" data-dt-order="disable">Email</th><th scope="col" data-dt-order="disable">Description</th><th scope="col">View</th><th scope="col">Actions</th></tr><tr id="endpoint-table-header"><th scope="col">Select</th><th scope="col">MAC Address</th><th scope="col">iPSK Endpoint Grouping</th><th scope="col">Expiration Date</th><th scope="col">Full Name</th><th scope="col">Email</th><th scope="col">Description</th><th scope="col">View</th><th scope="col">Actions</th></tr></thead><tbody>';
			
			for($idxId = $pageStart; $idxId < $pageEnd; $idxId++) {
							
				if($associationList[$idxId]['accountEnabled'] == 1){
					if($associationList[$idxId]['expirationDate'] == 0){
						$expiration = "Never";
					}elseif($associationList[$idxId]['expirationDate'] < time()){
						$expiration = '<span class="text-danger">Expired</span>';
					}else{
						$expiration = date($globalDateOutputFormat,$associationList[$idxId]['expirationDate']);
					}
				}else{
					$expiration = "Suspended";
				}

				$pageData['endpointAssociationList'] .= '<tr>';
				$pageData['endpointAssociationList'] .= '<td><input type="checkbox" class="endpoint-checkbox" data-id="'.$associationList[$idxId]['id'].'" data-mac="'.$associationList[$idxId]['macAddress'].'"></td>';
				$pageData['endpointAssociationList'] .= '<td>'.$associationList[$idxId]['macAddress'].'</td>';
				$pageData['endpointAssociationList'] .= '<td>'.$associationList[$idxId]['groupName'].'</td>';
				$pageData['endpointAssociationList'] .= '<td>'.$expiration.'</td>';
				$pageData['endpointAssociationList'] .= '<td>'.$associationList[$idxId]['fullName'].'</td>';
				$pageData['endpointAssociationList'] .= '<td>'.$associationList[$idxId]['email'].'</td>';
				$pageData['endpointAssociationList'] .= '<td>'.$associationList[$idxId]['description'].'</td>';
				$pageData['endpointAssociationList'] .= '<td><a class="epg-tableicons" module="endpoints" sub-module="view" row-id="'.$associationList[$idxId]['id'].'" href="#"><span data-feather="zoom-in"></span></a></td>';

				
				$actionRowData .= '<a class="dropdown-item action-tableicons" module="endpoints" sub-module="suspend" row-id="'.$associationList[$idxId]['id'].'" href="#">Suspend</a>';
				$actionRowData .= '<a class="dropdown-item action-tableicons" module="endpoints" sub-module="activate" row-id="'.$associationList[$idxId]['id'].'" href="#">Activate</a>';	
				$actionRowData .= '<a class="dropdown-item action-tableicons" module="endpoints" sub-module="extend" row-id="'.$associationList[$idxId]['id'].'" href="#">Extend</a>';	
				$actionRowData .= '<a class="dropdown-item action-tableicons" module="endpoints" sub-module="edit" row-id="'.$associationList[$idxId]['id'].'" href="#">Edit</a>';
				$actionRowData .= '<a class="dropdown-item action-tableicons" module="endpoints" sub-module="delete" row-id="'.$associationList[$idxId]['id'].'" href="#">Delete</a>';
				
				$pageData['endpointAssociationList'] .= '<td><div class="dropdown"><a class="dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="#"><span data-feather="more-vertical"></span></a><div class="dropdown-menu" aria-labelledby="dropdownMenuButton">'.$actionRowData.'</div></div></td>';	
				
				$actionRowData = "";
				
				$pageData['endpointAssociationList'] .= '</tr>';
			}
			
			$pageData['endpointAssociationList'] .= "</tbody></table>";
		}
	}

?>

<div class="card">
	<h4 class="text-center card-header bg-primary text-white pb-0 border-bottom-0">Managed iPSK Endpoints</h4>
	<h6 class="text-center card-header bg-primary text-white pt-0 border-top-0 fst-italic">Manage iPSK Endpoints to Add, View, Edit, and/or Delete</h6>
	<div class="card-header">
		<a id="newEndpoint" module="endpoints" sub-module="add" class="btn btn-primary custom-link text-white" href="#" role="button">Add Endpoint</a>
		<a id="bulkEndpoint" module="endpoints" sub-module="bulk" class="btn btn-primary custom-link text-white" href="#" role="button">Add Bulk Endpoints</a>
		<a id="bulkGroupChange" module="endpoints" sub-module="bulkgroupchange" class="btn btn-success custom-link text-white disabled" href="#" role="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Select one or more endpoints to enable bulk group change">Bulk Group Change</a>
		<span id="selectedCount" class="badge bg-info text-dark ms-2" style="display:none;">0 selected</span>
	</div>
	<div class="card-body">
			  <?php print $pageData['endpointAssociationList'];?>
		<div id="popupcontent"></div>
	</div>
</div>
<style>
	button.buttons-colvis {
    	background: #0d6efd !important;
	}
	
	/* Make checkboxes bigger and easier to click */
	#selectAll,
	.endpoint-checkbox {
		width: 20px;
		height: 20px;
		cursor: pointer;
	}
	
	/* Add padding to checkbox cells for easier clicking */
	#endpoint-table tbody td:first-child,
	#endpoint-table thead th:first-child {
		text-align: center;
		padding: 12px 8px;
	}
	
	/* Style for disabled bulk group change button */
	#bulkGroupChange.disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}
</style>
<script>
	$(function() {	
		feather.replace();
		
		// Initialize tooltip for bulk group change button
		var bulkGroupChangeTooltip = new bootstrap.Tooltip($('#bulkGroupChange')[0], {
			trigger: 'hover'
		});
	});
	
	// Store selected endpoint IDs across pagination
	var selectedEndpoints = {};
	
	// Handle Select All checkbox
	$("#selectAll").on('click', function() {
		var isChecked = $(this).prop('checked');
		$('.endpoint-checkbox:visible').each(function() {
			var endpointId = $(this).data('id');
			$(this).prop('checked', isChecked);
			if (isChecked) {
				selectedEndpoints[endpointId] = true;
			} else {
				delete selectedEndpoints[endpointId];
			}
		});
		updateBulkActionButton();
	});
	
	// Handle individual checkbox changes
	$(document).on('change', '.endpoint-checkbox', function() {
		var endpointId = $(this).data('id');
		if ($(this).prop('checked')) {
			selectedEndpoints[endpointId] = true;
		} else {
			delete selectedEndpoints[endpointId];
		}
		updateBulkActionButton();
		updateSelectAllState();
	});
	
	// Function to update Select All checkbox state
	function updateSelectAllState() {
		var visibleCheckboxes = $('.endpoint-checkbox:visible').length;
		var visibleChecked = $('.endpoint-checkbox:visible:checked').length;
		$('#selectAll').prop('checked', visibleCheckboxes > 0 && visibleCheckboxes === visibleChecked);
	}
	
	// Function to restore checkbox states after table redraw
	function restoreCheckboxStates() {
		$('.endpoint-checkbox').each(function() {
			var endpointId = $(this).data('id');
			if (selectedEndpoints[endpointId]) {
				$(this).prop('checked', true);
			}
		});
		updateSelectAllState();
		updateBulkActionButton();
	}
	
	// Function to update bulk action button state
	function updateBulkActionButton() {
		var checkedCount = Object.keys(selectedEndpoints).length;
		if (checkedCount > 0) {
			$('#bulkGroupChange').removeClass('disabled');
			$('#selectedCount').show().text(checkedCount + ' selected');
			// Disable tooltip when enabled
			var tooltip = bootstrap.Tooltip.getInstance($('#bulkGroupChange')[0]);
			if (tooltip) {
				tooltip.disable();
			}
		} else {
			$('#bulkGroupChange').addClass('disabled');
			$('#selectedCount').hide();
			// Enable tooltip when disabled
			var tooltip = bootstrap.Tooltip.getInstance($('#bulkGroupChange')[0]);
			if (tooltip) {
				tooltip.enable();
			}
		}
	}
	
	$(".epg-tableicons").click(function(event) {
		$.ajax({
			url: "ajax/getmodule.php",
			
			data: {
				module: $(this).attr('module'),
				'sub-module': $(this).attr('sub-module'),
				id: $(this).attr('row-id')
			},
			type: "POST",
			dataType: "html",
			success: function (data) {
				$('#popupcontent').html(data);
			},
			error: function (xhr, status) {
				$('#mainContent').html("<h6 class=\"text-center\"><span class=\"text-danger\">Error Loading Selection:</span>  Verify the installation/configuration and/or contact your system administrator!</h6>");
			}
		});
		
		event.preventDefault();
	});
	
	$(".action-tableicons").click(function(event) {
		$.ajax({
			url: "ajax/getmodule.php",
			
			data: {
				module: $(this).attr('module'),
				'sub-module': $(this).attr('sub-module'),
				id: $(this).attr('row-id')
			},
			type: "POST",
			dataType: "html",
			success: function (data) {
				$('#popupcontent').html(data);
			}
		});
		
		event.preventDefault();
	});
	
	$(".custom-link").click(function(event) {
		event.preventDefault();
		
		// Prevent action if button is disabled
		if($(this).hasClass('disabled')) {
			return false;
		}
		
		$.ajax({
			url: "ajax/getmodule.php",
			
			data: {
				module: $(this).attr('module'),
				'sub-module': $(this).attr('sub-module')
			},
			type: "POST",
			dataType: "html",
			success: function (data) {
				$('#popupcontent').html(data);
			},
			error: function (xhr, status) {
				$('#mainContent').html("<h6 class=\"text-center\"><span class=\"text-danger\">Error Loading Selection:</span>  Verify the installation/configuration and/or contact your system administrator!</h6>");
			}
		});
	});

	$(document).ready( function makeDataTable() {
		$('#endpoint-table thead #endpoint-table-filter th').each( function (index) {
        var title = $('#endpoint-table thead #endpoint-table-header th').eq( index ).text();
		if (/^(Select|View|Actions)$/.test(title)) {
			$(this).html('&nbsp;');
		} else {
			$(this).html('<input type="text" placeholder="Filter '+title+'" data-column-index="'+index+'" />');
		}
    	} );

		$("input[placeholder]").each(function () {
        	$(this).attr('size', $(this).attr('placeholder').length);
    	});
		
		$("#endpoint-table").DataTable({
			"columnDefs": [
				{
            		target: 0,
            		orderable: false
        		},
				{
            		target: 7,
            		orderable: false
        		},
				{
            		target: 8,
            		orderable: false
        		},
				{ responsivePriority: 1, targets: -1 },
        		{ responsivePriority: 2, targets: -2 },
    		],
			layout: {
        		bottomStart: {
            		buttons: ['colvis']
        		}
    		},
			"paging": true,
			"responsive": true,
			"stateSave": true,
			"lengthMenu": [ [15, 30, 45, 60, -1], [15, 30, 45, 60, "All"] ],
			"stateLoadParams": function(settings, data) {
  				for (i = 0; i < data.columns["length"]; i++) {
    				var col_search_val = data.columns[i].search.search;
    				if (col_search_val != "") {
      					$("input", $("#endpoint-table thead #endpoint-table-filter th")[i]).val(col_search_val);
    				}
  				}
			},
			"drawCallback": function(settings) {
				// Restore checkbox states after table redraw (pagination, sort, filter)
				restoreCheckboxStates();
			}
		});

		var table = $("#endpoint-table").DataTable();

		// Get State
		if (table.state.loaded() != null) {
			tableState = table.state();
			
			// Enable all columns
			table.column(4).visible(true);
			table.column(5).visible(true);
			table.column(6).visible(true);
		}

		$("#endpoint-table thead #endpoint-table-filter input").on( 'keyup change', function () {
		var colIndex = $(this).attr('data-column-index');
        table
            .column( colIndex )
            .search( this.value )
            .draw();
    	} );

		// Hide columns after keyup change event registered
		if (table.state.loaded() == null) {
			table.column(4).visible(false);
			table.column(5).visible(false);
			table.column(6).visible(false);
		} else {
			if (!tableState.columns[4].visible) {
				table.column(4).visible(false)
			}
			if (!tableState.columns[5].visible) {
				table.column(5).visible(false)
			}
			if (!tableState.columns[6].visible) {
				table.column(6).visible(false)
			}

		}


	} );

</script>