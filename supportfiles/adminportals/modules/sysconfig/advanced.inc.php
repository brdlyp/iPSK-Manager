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

	// Check if installer files are present
	$installerFilesPresent = false;
	if(is_file("../../adminportal/installer.php") || is_file("../../adminportal/installer.inc.php")){
		$installerFilesPresent = true;
	}
	
	// Generate installer card only if files are present
	$installerCard = "";
	if($installerFilesPresent){
		$installerCard = <<< HTML
		<div class="col" id="installerFilesCard">
			<div class="card h-100 border-warning">
          		<div class="card-header bg-warning text-dark"><i data-feather="alert-triangle"></i> Installation Files Detected</div>
          		<div class="card-body">
					<h6 class="card-title">Why is this showing?</h6>
					<p class="card-text small">
						The iPSK Manager installation files (<code>installer.php</code> and <code>installer.inc.php</code>) are still present in your <code>adminportal</code> directory.
					</p>
					<hr>
					<h6 class="card-title">Is this a problem?</h6>
					<p class="card-text small">
						If you've already completed the installation and have a fully configured database with your <code>config.php</code> file in place, these files are no longer needed and should be removed for security purposes.
					</p>
					<p class="card-text small">
						If this alert appeared after a system migration, git pull, or repository refresh, it's safe to delete these files as long as your database and configuration are working properly.
					</p>
					<hr>
					<h6 class="card-title">What should I do?</h6>
					<p class="card-text small">
						Click the button below to safely remove the installation files. The alert will disappear once they are deleted.
					</p>
				</div>
				<div class="card-footer">
					<button id="deleteInstallerFiles" module="sysconfig" sub-module="update" module-action="deleteinstaller" type="button" class="btn btn-danger btn-sm shadow">
						<i data-feather="trash-2"></i> Delete Installation Files
					</button>
					<span id="installerDeleteStatus" class="ms-2"></span>
				</div>
			</div>
		</div>
HTML;
	}

	print <<< HTML
<div class="container-fluid">
	<div class="row row-cols-1 row-cols-md-2 g-4">
		$installerCard
		<div class="col">
			<div class="card h-100">
          		<div class="card-header bg-primary text-white">Advanced Platform Settings</div>
          		<div class="card-body">
					<div class="form-label">
						<input type="checkbox" class="form-check-input checkbox-update advancedtab" base-value="1" value="{$advancedSettings['enable-portal-psk-edit-value']}" id="portalPskEditEnabled"{$advancedSettings['enable-portal-psk-edit']}>
						<label class="form-check-label" for="portalPskEditEnabled">Enable the "Manual PSK Editing" Portal Group Permission</label>
					</div>
					<div class="form-label">
						<input type="checkbox" class="form-check-input checkbox-update advancedtab" base-value="1" value="{$advancedSettings['enable-advanced-logging-value']}" id="advancedLoggingSettings"{$advancedSettings['enable-advanced-logging']}>
						<label class="form-check-label" for="advancedLoggingSettings">Enable Platform Logging Settings <a class="d-inline-block" data-bs-toggle="tooltip" title="" data-bs-original-title="Use with caution. Changing logging settings should be used for debugging purposes only." data-bs-placement="right"><i data-feather="alert-triangle"></i></a></label>
					</div>			
				</div>
				<div class="card-footer">
					<button id="updateadvanced" module="sysconfig" sub-module="update" module-action="advancedupdate" type="submit" class="btn btn-primary btn-sm shadow" disabled>Update Settings</button>
				</div>
			</div>
		</div>
	</div>
</div>
HTML;
?>
