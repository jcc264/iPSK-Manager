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

	


	
/**
 *@author	Gary Oppel (gaoppel@cisco.com)
 *@author	Hosuk Won (howon@cisco.com)
 *@contributor	Drew Betz (anbetz@cisco.com)
 */

	class BaseLDAPInterface {
		
		private $ldapHost;
		private $ldapDomain;
		private $ldapUsername;
		private $ldapPassword;
		private $ldapBaseDN;
		private $ldapsecure = true;
		private $iPSKManagerClass;
		
		function __construct($ldapServer = null, $domainName = null, $username = null, $password = null, $baseDN = null, $ldaps = true, $ipskManagerClass = false) {		
			$this->ldapHost = $ldapServer;
			$this->ldapDomain = $domainName;
			$this->ldapUsername = $username;
			$this->ldapPassword = $password;
			$this->ldapBaseDN = $baseDN;
			$this->ldapsecure = $ldaps;
			$this->iPSKManagerClass = $ipskManagerClass;
		}
		
		function set_ldapHost($hostname) {
			$this->ldapHost = $hostname;
		}
		
		function get_ldapHost(){
			return $this->ldapHost;
		}
		
		function set_ldapDomain($domain) {
			$this->ldapDomain = $domain;
		}
		
		function get_ldapDomain(){
			return $this->ldapDomain;
		}
		
		function set_Username($username) {
			$this->ldapUsername = $username;
		}
		
		function get_Username(){
			return $this->ldapUsername;
		}
		
		function set_Password($password) {
			$this->ldapPassword = $password;
		}
		
		function set_LDAPSecure($ldaps) {
			$this->ldapsecure = $ldaps;
		}
		
		function set_baseDN($basedn) {
			$this->ldapBaseDN = $basedn;
		}
		
		function get_baseDN(){
			return $this->ldapBaseDN;
		}
		
		function get_LDAPSecure() {
			return $this->ldapsecure;
		}
		
		function testLdapServer(){
		
			if($this->ldapsecure){
				$ldapConnection = ldap_connect("ldaps://".$this->ldapHost);
			}else{
				$ldapConnection = ldap_connect("ldap://".$this->ldapHost);
			}

			ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldapConnection, LDAP_OPT_REFERRALS, 0);
			
			$ldapBind = @ldap_bind($ldapConnection, $this->ldapUsername, $this->ldapPassword);
			
			if($ldapBind){
				return true;
			}else{
				return false;
			}
			
		}
		function authenticateUser($username, $password){
			
			if($this->ldapsecure){
				$ldapConnection = ldap_connect("ldaps://".$this->ldapHost);
			}else{
				$ldapConnection = ldap_connect("ldap://".$this->ldapHost);
			}

			ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldapConnection, LDAP_OPT_REFERRALS, 0);
			
			$ldapBind = @ldap_bind($ldapConnection, $this->ldapUsername, $this->ldapPassword);
			
			if($ldapBind){
				
				if(strpos($username,"@")){
					$filter = '(userPrincipalName='.$username.')';
				}elseif(strpos($username,"\\")){
					$username = substr($username,strpos($username,"\\") + 1);
					$filter = '(sAMAccountName='.$username.')';
				}else{
					$filter = '(sAMAccountName='.$username.')';
				}
				
				$attributes = array("name", "mail", "samaccountname", "objectSid", "memberof", "userPrincipalName");
				$result = ldap_search($ldapConnection, $this->ldapBaseDN, $filter, $attributes);

				$entries = ldap_get_entries($ldapConnection, $result);  
						
				if($entries['count'] == 1){
					$userDN = $entries[0]['dn'];
					$ldapBind = @ldap_bind($ldapConnection, $userDN, $password);
					
					if($ldapBind){
						if($this->iPSKManagerClass){
							//LOG::Entry
							$logData = $this->iPSKManagerClass->generateLogData(Array("base64SID"=>base64_encode($entries[0]['objectsid'][0])));
							$logMessage = "REQUEST:SUCCESS;ACTION:AUTHENTICATE-USER;USERNAME:".$username.";AUTHDIRECTORY:".$this->ldapDomain.";";
							$this->iPSKManagerClass->addLogEntry($logMessage, __FILE__, __FUNCTION__, __CLASS__, __METHOD__, __LINE__, $logData);
						}
						$_SESSION['memberOf'] = $entries[0]['memberof'];
						$_SESSION['sAMAccountName'] = $entries[0]['samaccountname'][0];
						$_SESSION['userPrincipalName'] = $entries[0]['userprincipalname'][0];
						$_SESSION['fullName'] = (isset($entries[0]['name'][0])) ? $entries[0]['name'][0] : '';
						$_SESSION['emailAddress'] = (isset($entries[0]['mail'][0])) ? $entries[0]['mail'][0] : '';
						$_SESSION['logonUsername'] = $username;
						$_SESSION['logonSID'] = convertBinSID($entries[0]['objectsid'][0]);
						$_SESSION['logonDN'] = $userDN;
						$_SESSION['logonDomain'] = $this->ldapDomain;
						$_SESSION['authenticationGranted'] = true;
						$_SESSION['authenticationTimestamp'] = time();
						$_SESSION['logonTime'] = time();
						$_SESSION['loggedIn'] = true;
						
						return true;
					}else{
						if($this->iPSKManagerClass){
							//LOG::Entry
							$logData = $this->iPSKManagerClass->generateLogData();
							$logMessage = "REQUEST:FAILURE[ldap_user_authn_failure];ACTION:AUTHENTICATE-USER;USERNAME:".$username.";AUTHDIRECTORY:".$this->ldapDomain.";";
							$this->iPSKManagerClass->addLogEntry($logMessage, __FILE__, __FUNCTION__, __CLASS__, __METHOD__, __LINE__, $logData);
						}
						return false;
					}
				}else{
					if($this->iPSKManagerClass){
						//LOG::Entry
						$logData = $this->iPSKManagerClass->generateLogData();
						$logMessage = "REQUEST:FAILURE[ldap_user_lookup_failed];ACTION:AUTHENTICATE-USER;USERNAME:".$username.";AUTHDIRECTORY:".$this->ldapDomain.";";
						$this->iPSKManagerClass->addLogEntry($logMessage, __FILE__, __FUNCTION__, __CLASS__, __METHOD__, __LINE__, $logData);
					}
					return false;
				}
			}else{
				if($this->iPSKManagerClass){
					//LOG::Entry
					$logData = $this->iPSKManagerClass->generateLogData();
					$logMessage = "REQUEST:FAILURE[ldap_server_logon_failure];ACTION:AUTHENTICATE-USER;USERNAME:".$username.";AUTHDIRECTORY:".$this->ldapDomain.";";
					$this->iPSKManagerClass->addLogEntry($logMessage, __FILE__, __FUNCTION__, __CLASS__, __METHOD__, __LINE__, $logData);
				}
				return false;
			}
		}
	}
?>