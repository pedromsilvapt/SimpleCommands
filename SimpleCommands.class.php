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
		
		//Goes through each character
		for ($pass = 0; $pass<count($paramsString); $pass++){
			$char = $paramsString[$pass];
			//Checks if this char is the first of the parameter
			if ($type === 0){
				//Ignores spaces...
				if ($char != ' '){
					//Maybe it's an number
					if (in_array($char, $integersSimbols)){
						$type = 'number';
						$separedParams[$currentParam]['type'] = 'number';
						$separedParams[$currentParam]['value'] = $char;
					//Or maybe it's a complex string (delimited by quotation marks [ "" ])
					} elseif ($char == '"'){
						$type = 'string2';
						$separedParams[$currentParam]['type'] = 'string';
					//Or it is a simple string (can't contain spaces)
					} else {
						$type = 'string';
						$separedParams[$currentParam]['type'] = 'string';
						$separedParams[$currentParam]['value'] = $char;
					}
				}
			//The current char is in the middle of a parameter
			} else {
				//If it's a space in an number or simple string, then the parameter finishes.
				if ($type != 'string2' and $char == ' '){
					//Parses the string to a PHP integer or float
					if ($separedParams[$currentParam]['type'] === 'number'){
						if ($comma == 1){
							$separedParams[$currentParam]['type'] === 'float';
							$separedParams[$currentParam]['value'] = (float)$separedParams[$currentParam]['value'];
						} else {
							$separedParams[$currentParam]['type'] === 'integer';
							$separedParams[$currentParam]['value'] = (integer)$separedParams[$currentParam]['value'];
						}
						$comma = 0;
					}
					$type = 0;
					$currentParam++;
				//The current param is a number
				} elseif ($type === 'number'){
					//Yes, numbers can contain decimal cases
					if($char == '.'){
						if ($comma == 0){
							$comma = 1;
						//More than one point makes the current param a string
						} else {
							$type = 'string';
							$separedParams[$currentParam]['type'] === 'string';
						}
					//And any strange character makes it a string too
					} elseif (!in_array($char, $integersSimbols)) {
						$type = 'string';
						$separedParams[$currentParam]['type'] === 'string';
					}
					$separedParams[$currentParam]['value'] .= $char;
				//Or the current param is a simple string
				} elseif ($type === 'string'){
					$separedParams[$currentParam]['value'] .= $char;
				//Or a complex one
				} elseif ($type === 'string2'){
					//if current char is a backslash, escapes the next char
					//The backslash is taken out of the string
					if ($char == '\\'){
						$separedParams[$currentParam]['value'] .= $paramsString[$pass+1];
						$pass++;
					//If it's a quotation mark, and was not escaped by any backslash, so it's the end of the string
					} elseif ($char == '"'){
						$currentParam++;
						$type = 0;
					//Or, it's just another char to add to the string
					} else {
						$separedParams[$currentParam]['value'] .= $char;
					}
				}
			}
		}
		
		//The command end's lefting a complex string open, the the parse fails
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