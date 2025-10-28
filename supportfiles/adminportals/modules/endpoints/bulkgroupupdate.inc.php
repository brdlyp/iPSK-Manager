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

	$successCount = 0;
	$failureCount = 0;
	$resultsHtml = "";
	$endpointList = array();
	
	if(isset($sanitizedInput['associationGroup']) && isset($sanitizedInput['selectedEndpointIds'])) {
		
		// Get the endpoint group authorization details
		$endpointGroupAuthorization = $ipskISEDB->getAuthorizationTemplatesbyEPGroupId($sanitizedInput['associationGroup']);
		$endpointGroupName = $endpointGroupAuthorization['groupName'];
		
		// Split the comma-separated list of endpoint IDs
		$endpointIds = explode(',', $sanitizedInput['selectedEndpointIds']);
		
		// Build results table header
		$resultsHtml .= '<table class="table table-sm table-bordered"><thead><tr><th>MAC Address</th><th>Status</th><th>Message</th></tr></thead><tbody>';
		
		// Process each endpoint
		foreach($endpointIds as $associationId) {
			$associationId = trim($associationId);
			
			if(!is_numeric($associationId) || $associationId == 0) {
				continue;
			}
			
			try {
				// Get endpoint details by association ID
				$endpoint = $ipskISEDB->getEndpointByAssociationId($associationId);
				
				if(!$endpoint) {
					$resultsHtml .= '<tr class="table-danger"><td>Unknown</td><td><span class="text-danger">Failed</span></td><td>Endpoint not found</td></tr>';
					$failureCount++;
					continue;
				}
				
				$macAddress = $endpoint['macAddress'];
				
				// Determine PSK based on group type
				if($endpointGroupAuthorization['ciscoAVPairPSK'] == "*devicerandom*"){
					$randomPassword = $ipskISEDB->generateRandomPassword($endpointGroupAuthorization['pskLength']);
					$randomPSK = "psk=".$randomPassword;
				}elseif($endpointGroupAuthorization['ciscoAVPairPSK'] == "*userrandom*"){
					$userPsk = $ipskISEDB->getUserPreSharedKey($sanitizedInput['associationGroup'], $endpoint['createdBy']);
					if(!$userPsk){
						$randomPassword = $ipskISEDB->generateRandomPassword($endpointGroupAuthorization['pskLength']);
						$randomPSK = "psk=".$randomPassword;
					}else{
						$randomPassword = $userPsk;
						$randomPSK = "psk=".$randomPassword;
					}
				}else{
					$randomPassword = $endpointGroupAuthorization['ciscoAVPairPSK'];
					$randomPSK = "psk=".$randomPassword;
				}
				
				// Calculate new expiration date
				if($endpointGroupAuthorization['termLengthSeconds'] == 0){
					$duration = $endpointGroupAuthorization['termLengthSeconds'];
				}else{
					$duration = time() + $endpointGroupAuthorization['termLengthSeconds'];
				}
				
				// Update the endpoint
				$updateResult = $ipskISEDB->updateEndpoint(
					$endpoint['endpointId'],
					$endpoint['fullName'], 
					$endpoint['description'], 
					$endpoint['emailAddress'], 
					$_SESSION['logonSID'], 
					$randomPSK, 
					$endpointGroupAuthorization['vlan'], 
					$endpointGroupAuthorization['dacl'], 
					$duration
				);
				
				if($updateResult !== false) {
					// Delete old association
					$deleteResult = $ipskISEDB->deleteEndpointAssociationbyId($associationId);
					
					// Create new association
					$addResult = $ipskISEDB->addEndpointAssociation(
						$endpoint['endpointId'], 
						$macAddress, 
						$sanitizedInput['associationGroup'], 
						$_SESSION['logonSID']
					);
					
					if($addResult) {
						$resultsHtml .= '<tr class="table-success"><td>'.$macAddress.'</td><td><span class="text-success">Success</span></td><td>Updated to group: '.$endpointGroupName.'</td></tr>';
						$successCount++;
						
						//LOG::Entry
						$logData = $ipskISEDB->generateLogData(Array("sanitizedInput"=>$sanitizedInput, "endpoint"=>$endpoint));
						$logMessage = "REQUEST:SUCCESS;ACTION:BULKGROUPCHANGE;METHOD:UPDATE-ENDPOINT-GROUP;MAC:".$macAddress.";NEW-GROUP:".$endpointGroupName.";REMOTE-IP:".$_SERVER['REMOTE_ADDR'].";USERNAME:".$_SESSION['logonUsername'].";SID:".$_SESSION['logonSID'].";";
						$ipskISEDB->addLogEntry($logMessage, __FILE__, __FUNCTION__, __CLASS__, __METHOD__, __LINE__, $logData);
					} else {
						$resultsHtml .= '<tr class="table-danger"><td>'.$macAddress.'</td><td><span class="text-danger">Failed</span></td><td>Failed to create new association</td></tr>';
						$failureCount++;
						
						//LOG::Entry
						$logData = $ipskISEDB->generateLogData(Array("sanitizedInput"=>$sanitizedInput, "endpoint"=>$endpoint));
						$logMessage = "REQUEST:FAILURE[unable_to_create_association];ACTION:BULKGROUPCHANGE;MAC:".$macAddress.";REMOTE-IP:".$_SERVER['REMOTE_ADDR'].";USERNAME:".$_SESSION['logonUsername'].";SID:".$_SESSION['logonSID'].";";
						$ipskISEDB->addLogEntry($logMessage, __FILE__, __FUNCTION__, __CLASS__, __METHOD__, __LINE__, $logData);
					}
				} else {
					$resultsHtml .= '<tr class="table-danger"><td>'.$macAddress.'</td><td><span class="text-danger">Failed</span></td><td>Failed to update endpoint</td></tr>';
					$failureCount++;
					
					//LOG::Entry
					$logData = $ipskISEDB->generateLogData(Array("sanitizedInput"=>$sanitizedInput, "endpoint"=>$endpoint));
					$logMessage = "REQUEST:FAILURE[unable_to_update_endpoint];ACTION:BULKGROUPCHANGE;MAC:".$macAddress.";REMOTE-IP:".$_SERVER['REMOTE_ADDR'].";USERNAME:".$_SESSION['logonUsername'].";SID:".$_SESSION['logonSID'].";";
					$ipskISEDB->addLogEntry($logMessage, __FILE__, __FUNCTION__, __CLASS__, __METHOD__, __LINE__, $logData);
				}
				
			} catch (Exception $e) {
				$resultsHtml .= '<tr class="table-danger"><td>'.($macAddress ?? 'Unknown').'</td><td><span class="text-danger">Error</span></td><td>'.htmlspecialchars($e->getMessage()).'</td></tr>';
				$failureCount++;
			}
		}
		
		$resultsHtml .= '</tbody></table>';
		
		//LOG::Entry - Summary
		$logData = $ipskISEDB->generateLogData(Array("sanitizedInput"=>$sanitizedInput, "successCount"=>$successCount, "failureCount"=>$failureCount));
		$logMessage = "REQUEST:SUCCESS;ACTION:BULKGROUPCHANGE;METHOD:BULK-UPDATE-SUMMARY;SUCCESS-COUNT:".$successCount.";FAILURE-COUNT:".$failureCount.";REMOTE-IP:".$_SERVER['REMOTE_ADDR'].";USERNAME:".$_SESSION['logonUsername'].";SID:".$_SESSION['logonSID'].";";
		$ipskISEDB->addLogEntry($logMessage, __FILE__, __FUNCTION__, __CLASS__, __METHOD__, __LINE__, $logData);
	}

	$htmlbody = <<<HTML
<!-- Modal -->
<div class="modal fade" id="bulkGroupUpdateResults" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Bulk Endpoint Group Change Results</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					
				</button>
			</div>
			<div class="modal-body">
				<div class="row mb-3">
					<div class="col">
						<div class="alert alert-info">
							<h5>Summary</h5>
							<div class="row">
								<div class="col-md-6">
									<p><strong>Total Processed:</strong> {$totalProcessed}</p>
									<p class="text-success"><strong>Successful:</strong> {$successCount}</p>
								</div>
								<div class="col-md-6">
									<p class="text-danger"><strong>Failed:</strong> {$failureCount}</p>
									<p><strong>New Group:</strong> {$endpointGroupName}</p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col">
						<h6>Detailed Results:</h6>
						<div style="max-height: 400px; overflow-y: auto;">
							{$resultsHtml}
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" id="returnToEndpoints" class="btn btn-primary shadow">Return to Endpoints</button>
				<button type="button" class="btn btn-secondary shadow" data-bs-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
<script>
	$("#bulkGroupUpdateResults").modal('show');

	$(function() {	
		feather.replace()
	});
	
	$("#returnToEndpoints").click(function(){
		event.preventDefault();
		
		// Clear selections
		if (typeof selectedEndpoints !== 'undefined') {
			selectedEndpoints = {};
		}
		
		const modal = bootstrap.Modal.getInstance(document.getElementById('bulkGroupUpdateResults'));
		modal.hide();
		
		$.ajax({
			url: "ajax/getmodule.php",
			
			data: {
				module: 'endpoints'
			},
			type: "POST",
			dataType: "html",
			success: function (data) {
				$('#mainContent').html(data);
			},
			error: function (xhr, status) {
				$('#mainContent').html("<h6 class=\"text-center\"><span class=\"text-danger\">Error Loading Selection:</span>  Verify the installation/configuration and/or contact your system administrator!</h6>");
			}
		});
	});
	
	// Auto-refresh the endpoints view when modal is closed
	$('#bulkGroupUpdateResults').on('hidden.bs.modal', function (e) {
		// Clear selections
		if (typeof selectedEndpoints !== 'undefined') {
			selectedEndpoints = {};
		}
		
		$.ajax({
			url: "ajax/getmodule.php",
			
			data: {
				module: 'endpoints'
			},
			type: "POST",
			dataType: "html",
			success: function (data) {
				$('#mainContent').html(data);
			}
		});
	});
</script>
HTML;

	// Calculate total processed
	$totalProcessed = $successCount + $failureCount;
	
	// Replace the variables in the HTML
	$htmlbody = str_replace('{$totalProcessed}', $totalProcessed, $htmlbody);
	$htmlbody = str_replace('{$successCount}', $successCount, $htmlbody);
	$htmlbody = str_replace('{$failureCount}', $failureCount, $htmlbody);
	$htmlbody = str_replace('{$endpointGroupName}', htmlspecialchars($endpointGroupName ?? 'N/A'), $htmlbody);
	$htmlbody = str_replace('{$resultsHtml}', $resultsHtml, $htmlbody);

	print $htmlbody;
?>

