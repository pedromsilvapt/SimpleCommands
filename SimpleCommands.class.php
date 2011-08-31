<?php

require('Command.class.php');

class SimpleCommands {
	
	private $regexMatch;
	private $commandsFolder;
	private $commandsInstances;
	private $false;
	private $true;
	
	public function getRegExMatch(){
		return($this->regexMatch);
	}
	
	public function getCommandsFolder(){
		return($this->commandsFolder);
	}
	
	public function validateCommandName($commandName){
		if (preg_match('/^([0-9a-zA-Z])*$/', $commandName) == 1){
			return(true);
		} else {
			return(false);
		}
	}
	
	public function validateCommand($command){
		$command = trim($command);
		
		if (!is_string($command)){
			return(false);
		}
		
		$matches = preg_match($this->regexMatch, $command);
		
		if ($matches == 0){
			return(false);
		} else {
			return(true);
		}
	}
	
	public function commandLoaded($commandName){
		if ($this->validateCommandName($commandName)){
			return(false);
		}
		if (isset($this->commandsInstances[$commandName])){
			return(true);
		} else {
			return(false);
		}
	}
	
	private function &getReference(&$var){
		return $var;
	}
	
	public function loadCommand($commandName){
		if (!$this->commandExists($commandName)){
			return(false);
		}
		
		if ($this->commandLoaded($commandName)){
			return $this->commandsInstances[$commandName];
		}
		
		include_once($this->commandsFolder.'\\'.$commandName.'.command.php');
		$className = $commandName.'_Command';
		
		if (!class_exists($className)){
			return(false);
		}
		
		$reflectiveCommand = new ReflectionClass('Command');
		$reflectiveClass = new ReflectionClass($commandName.'_Command');
		
		if (!$reflectiveClass->isSubclassOf($reflectiveCommand)){
			return(false);
		}
		
		if ($reflectiveClass->getFileName() != $this->commandsFolder.'\\'.$commandName.'.command.php'){
			return(false);
		}
		
		$this->commandsInstances[$commandName] = new $className;
		if (!$this->commandsInstances[$commandName] instanceof $className){
			unset($this->commandsInstances[$commandName]);
			return(false);
		}
		
		return(true);
	}
	
	public function commandExists($commandName){
		if ($this->validateCommandName($commandName) == false){
			return(false);
		}
		if (file_exists($this->commandsFolder.'\\'.$commandName.'.command.php') and is_file($this->commandsFolder.'\\'.$commandName.'.command.php')){
			return(true);
		} else {
			return(false);
		}
	}
	
	private function splitParams($paramsString){
		if (!is_string($paramsString)){
			return(false);
		}
		
		$integersSimbols = Array('-', '+', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
		
		$paramsString = trim($paramsString);
		$paramsString = str_split($paramsString, 1);
		$separedParams = Array();
		
		$in_string = false;
		$type = 0;
		$currentParam = 0;
		$comma = 0;
		
		for ($pass = 0; $pass<count($paramsString); $pass++){
			$char = $paramsString[$pass];
			if ($type === 0){
				if ($char != ' '){
					if (in_array($char, $integersSimbols)){
						$type = 'integer';
						$separedParams[$currentParam]['type'] = 'integer';
						$separedParams[$currentParam]['value'] = $char;
					} elseif ($char == '"'){
						$type = 'string2';
						$separedParams[$currentParam]['type'] = 'string';
					} else {
						$type = 'string';
						$separedParams[$currentParam]['type'] = 'string';
						$separedParams[$currentParam]['value'] = $char;
					}
				}
			} else {
				if ($type != 'string2' and $char == ' '){
					if ($separedParams[$currentParam]['type'] === 'integer'){
						$separedParams[$currentParam]['value'] = (integer)$separedParams[$currentParam]['value'];
					}
					$type = 0;
					$currentParam++;
				} elseif ($type === 'integer'){
					if (in_array($char, $integersSimbols)){
						$separedParams[$currentParam]['value'] .= $char;
					} elseif($char == '.'){
						if ($comma == 0){
							$comma = 1;
						} else {
							$type = 'string';
						}
					} else {
						$type = 'string';
					}
					$separedParams[$currentParam]['value'] .= $char;
				} elseif ($type === 'string'){
					$separedParams[$currentParam]['value'] .= $char;
				} elseif ($type === 'string2'){
					if ($char == '\\'){
						$separedParams[$currentParam]['value'] .= $paramsString[$pass+1];
						$pass++;
					} elseif ($char == '"'){
						$currentParam++;
						$type = 0;
					} else {
						$separedParams[$currentParam]['value'] .= $char;
					}
				}
			}
		}

		if ($type === 'string2'){
			return(false);
		}
		return($separedParams);
	}
	
	public function parseCommand($command){
		$command = trim($command);
		if ($this->validateCommand($command) == false){
			return(false);
		}
		
		$command = substr($command, 1);
		
		$commandParams = explode(' ', $command, 2);
		
		
		$commandParts['name'] = $commandParams[0];
		if (isset($commandParams[1])){
			$commandParams = $this->splitParams($commandParams[1]);
			
			foreach ($commandParams as $index => $param){
				$commandParts['params'][] = $param['value'];
			}
		}
		
		return($commandParts);
	}
	
	public function executeCommand($command){
		$commandParts = $this->parseCommand($command);
		if ($commandParts === false){
			return(false);
		}
		
		$commandInstance = &$this->loadCommand($commandParts['name']);
		
		if ($commandInstance === false){
			return(false);
		}
		
		$commandInstance = &$this->commandsInstances[$commandParts['name']];
		//if (isset())
		$return = call_user_func_array(array(&$commandInstance, 'execute'), $commandParts['params']);
		
		return($return);
	}
	
	public function __construct($commandsFolder){
		$this->regexMatch = '@^/([0-9a-zA-Z]*)((?:(?: )+(?:"[^"\\\r\n]*(?:\\.[^"\\\r\n]*)*"|[+-](?:(?:\b[0-9]+)?\.)?[0-9]+\b|[0-9a-zA-Z])+)+)?$@';
		if (file_exists($commandsFolder) and is_dir($commandsFolder)){
			$this->commandsFolder = $commandsFolder;
		}
	}
	
}



?>