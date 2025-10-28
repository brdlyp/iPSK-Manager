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
 

	//Clear Variables and set to blank
	$pageData['endpointGroupList'] = "";
	
	$endpointGroups = $ipskISEDB->getEndpointGroupsAndAuthz();
	
	if($endpointGroups){
		$pageData['endpointGroupList'] .= '<select name="associationGroup" id="associationGroup" class="form-select shadow form-validation">';
		
		while($row = $endpointGroups->fetch_assoc()) {		
			if($row["visible"] == true){
				if($row['termLengthSeconds'] == 0){
					$termLength = "No Expiry";
				}else{
					$termLength = ($row['termLengthSeconds'] / 60 / 60 / 24) . " Days";
				}

				if($row['ciscoAVPairPSK'] == "*userrandom*"){
					$keyType = "Randomly Chosen per User";
				}elseif($row['ciscoAVPairPSK'] == "*devicerandom*"){
					$keyType = "Randomly Chosen per Device";
				}else{
					$keyType = "Common PSK";
				}
				
				$pageData['endpointGroupList'] .= "<option data-keytype=\"$keyType\" data-term=\"$termLength\" value=\"".$row['id']."\">".$row['groupName']."</option>";
			}
		}
		$pageData['endpointGroupList'] .= "</select>";
	}

	$htmlbody = <<<HTML
<!-- Modal -->
<div class="modal fade" id="bulkGroupChangeDialog" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bulkGroupChangeTitle">Bulk Endpoint Group Change</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					
				</button>
			</div>
			<div class="modal-body">
				<form id="bulkGroupChangeForm" class="needs-validation" novalidate>
					<div class="row">
						<div class="col mt-2 shadow mx-auto p-2 bg-white border border-primary">
							<h6>Select New Endpoint Group:</h6>
							{$pageData['endpointGroupList']}
							<div class="container-fluid">
								<div class="row">
									<div class="col-md">
										<p><small>Maximum access duration:&nbsp;<span id="duration" class="text-danger count">-</span></small></p>
									</div>
									<div class="col-md">
										<p><small>Pre Shared Key Type:&nbsp;<span id="keyType" class="text-danger count">-</span></small></p>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col mt-2 shadow mx-auto p-2 bg-white border border-warning">
							<h6 class="text-warning"><span data-feather="alert-triangle"></span> Selected Endpoints:</h6>
							<div id="selectedEndpointsList" class="alert alert-info">
								<p><strong>Total Selected: <span id="totalSelected">0</span> endpoints</strong></p>
								<div id="endpointsList" style="max-height: 200px; overflow-y: auto;">
									<ul id="endpointsListItems" class="mb-0"></ul>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col mt-2 shadow mx-auto p-2 bg-white border border-danger">
							<div class="alert alert-warning mb-0">
								<h6 class="text-danger"><span data-feather="alert-circle"></span> Warning</h6>
								<p class="mb-0"><strong>This action will:</strong></p>
								<ul>
									<li>Change the endpoint group for all selected endpoints</li>
									<li>Generate new Pre-Shared Keys based on the new group's settings</li>
									<li>Update expiration dates based on the new group's term length</li>
									<li>Trigger Cisco ISE updates for all affected endpoints</li>
								</ul>
								<p class="text-danger mb-0"><strong>This action cannot be undone!</strong></p>
							</div>
						</div>
					</div>
					<input type="hidden" id="selectedEndpointIds" name="selectedEndpointIds" value="">
				</form>
			</div>
			<div class="modal-footer">
				<button id="bulkgroupupdate" module="endpoints" sub-module="bulkgroupupdate" class="btn btn-success shadow" disabled>Update Endpoint Groups</button>
				<button type="button" class="btn btn-secondary shadow" data-bs-dismiss="modal">Cancel</button>
			</div>
		</div>
	</div>
</div>
<script>
	var failure;
	
	$("#bulkGroupChangeDialog").modal('show');

	$(function() {	
		feather.replace()
	});
	
	// Populate selected endpoints list
	function populateSelectedEndpoints() {
		var selectedEndpointsList = [];
		var selectedIds = [];
		
		// Access the persistent selectedEndpoints object from the parent scope
		if (typeof selectedEndpoints !== 'undefined' && selectedEndpoints) {
			selectedIds = Object.keys(selectedEndpoints);
			
			// Get MAC addresses for selected endpoints
			$('.endpoint-checkbox').each(function() {
				var id = String($(this).data('id'));
				if (selectedEndpoints[id]) {
					selectedEndpointsList.push({
						id: id,
						mac: $(this).data('mac')
					});
				}
			});
		} else {
			// Fallback: Get currently checked checkboxes
			$('.endpoint-checkbox:checked').each(function() {
				var id = String($(this).data('id'));
				selectedEndpointsList.push({
					id: id,
					mac: $(this).data('mac')
				});
				selectedIds.push(id);
			});
		}
		
		$('#totalSelected').text(selectedIds.length);
		$('#endpointsListItems').empty();
		
		selectedEndpointsList.forEach(function(endpoint) {
			$('#endpointsListItems').append('<li>' + endpoint.mac + '</li>');
		});
		
		// Store the IDs as a comma-separated string
		$('#selectedEndpointIds').val(selectedIds.join(','));
		
		// Enable the update button if endpoints are selected and a group is chosen
		if (selectedIds.length > 0 && $('#associationGroup').val()) {
			$('#bulkgroupupdate').removeAttr('disabled');
		}
	}
	
	// Call on load
	populateSelectedEndpoints();
	
	$("#bulkgroupupdate").click(function(){
		event.preventDefault();
		
		failure = formFieldValidation();

		if(failure){
			return false;
		}
		
		// Confirm action
		if (!confirm('Are you sure you want to change the endpoint group for ' + $('#totalSelected').text() + ' endpoint(s)? This action cannot be undone.')) {
			return false;
		}
		
		const modal = bootstrap.Modal.getInstance(document.getElementById('bulkGroupChangeDialog'));
		modal.hide();
		
		$.ajax({
			url: "ajax/getmodule.php",
			
			data: {
				module: $(this).attr('module'),
				'sub-module': $(this).attr('sub-module'),
				associationGroup: $("#associationGroup").val(),
				selectedEndpointIds: $("#selectedEndpointIds").val()
			},
			type: "POST",
			dataType: "html",
			success: function (data) {
				$('#popupcontent').html(data);
			},
			error: function (xhr, status) {
				$('#mainContent').html("<h6 class=\"text-center\"><span class=\"text-danger\">Error Loading Selection:</span>  Verify the installation/configuration and/or contact your system administrator!</h6>");
			},
			complete: function (xhr, status) {
				//$('#showresults').slideDown('slow')
			}
		});
	});
	
	$("#associationGroup").change(function() {
		var duration = "";
		var keyType = "";
		$( "#associationGroup option:selected" ).each(function() {
			duration = $(this).attr("data-term");
			keyType = $(this).attr("data-keytype");
			$( "#duration" ).html( duration );
			$( "#keyType" ).html( keyType );
		});
		
		// Enable update button if endpoints are selected
		if ($('#selectedEndpointIds').val() && $(this).val()) {
			$('#bulkgroupupdate').removeAttr('disabled');
		} else {
			$('#bulkgroupupdate').attr('disabled', 'disabled');
		}
	});
	
	$("#associationGroup").trigger("change");
	
	
</script>
HTML;

print $htmlbody;
?>

