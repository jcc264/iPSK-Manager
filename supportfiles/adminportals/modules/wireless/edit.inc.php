<?php

/**
 *@license
 *Copyright (c) 2019 Cisco and/or its affiliates.
 *
 *This software is licensed to you under the terms of the Cisco Sample
 *Code License, Version 1.1 (the "License"). You may obtain a copy of the
 *License at
 *
 *			   https://developer.cisco.com/docs/licenses
 *
 *All use of the material herein must be in accordance with the terms of
 *the License. All rights not expressly granted by the License are
 *reserved. Unless required by applicable law or agreed to separately in
 *writing, software distributed under the License is distributed on an "AS
 *IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
 *or implied.
 */
	
	$id = filter_var($_POST['id'],FILTER_VALIDATE_INT);

	if($id > 0){

		$wirelessNetwork = $ipskISEDB->getWirelessNetworkById($id);
	
$htmlbody = <<<HTML
<!-- Modal -->
<div class="modal fade" id="updatewireless" tabindex="-1" role="dialog" aria-labelledby="updatewirelessModal" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Add Wireless Network</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
		<label class="font-weight-bold" for="ssidName">Wireless Network SSID:</label>
		<div class="form-group input-group-sm font-weight-bold">
			<input type="text" class="form-control shadow form-validation" validation-state="required" id="ssidName" name="ssidName" value="{$wirelessNetwork['ssidName']}" required>
			<div class="invalid-feedback">Please enter a valid SSID Name</div>
		</div>
		<label class="font-weight-bold" for="ssidDescription">Description:</label>
		<div class="form-group input-group-sm font-weight-bold">
			<input type="text" class="form-control shadow" id="ssidDescription" name="ssidDescription" value="{$wirelessNetwork['ssidDescription']}">
		</div>
	  </div>
      <div class="modal-footer">
		<input type="hidden" id="id" value="{$wirelessNetwork['id']}">
		<a id="update" href="#" module="wireless" sub-module="update" role="button" class="btn btn-primary shadow" data-dismiss="modal">Update</a>
        <button type="button" class="btn btn-secondary shadow" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<script>
	var failure;
	
	$("#updatewireless").modal({show: true, backdrop: true});
	
	$("#update").click(function(){
		event.preventDefault();
		
		failure = formFieldValidation();
		
		if(failure){
			return false;
		}
		
		$('.modal-backdrop').remove();
		
		$.ajax({
			url: "ajax/getmodule.php",
			
			data: {
				module: $(this).attr('module'),
				'sub-module': $(this).attr('sub-module'),
				id: $("#id").val(),
				ssidName: $("#ssidName").val(),
				ssidDescription: $("#ssidDescription").val()
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
</script>
HTML;

print $htmlbody;
	}
?>