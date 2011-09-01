<?php

/*
 
#    This program is free software; you can redistribute it and/or
 
#    modify it under the terms of the GNU General Public License
 
#    as published by the Free Software Foundation; either version 2
 
#    of the License, or (at your option) any later version.
 
#
 
#    This program is distributed in the hope that it will be useful,
 
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
 
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 
#    GNU General Public License for more details.
 
#    http://www.gnu.org/licenses/gpl.txt
 
#
 
*/
 
 
 
 /******
 
 *
 
 * SimpleCommands - library to parse commands from strings in PHP.
 
 * 
 
 * @author	Scorch (aka Scorchpt or Scorchsw)
 
 * @access	public
 
 * @version	Alpha 0.1
 
 * @link	http://scorch.isgreat.org/
 
 *
 
 ******/

require('Command.class.php');

class SimpleCommands {
	
	private $regexMatch;
	private $commandsFolder;
	private $commandsInstances;
	
	private $autoHelp;
	
	public function getRegExMatch(){
		return($this->regexMatch);
	}
	
	public function getCommandsFolder(){
		return($this->commandsFolder);
	}
	
	/*
	* Checks if the given string is a valid command name
	*	
	* @param string commandName - The command name to check the validity
	* @return boolean - True in case the command name is valid, false if not
	*/
	public function validateCommandName($commandName){
		if (preg_match('/^([0-9a-zA-Z])*$/', $commandName) == 1){
			return(true);
		} else {
			return(false);
		}
	}
	
	/*
	* This function validates a command to check if it meets the requirements
	*
	* @param string command - The input string to check
	* @return boolean - Returns true in case the command is valid, false in case the command is invalid
	*/
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
	
	/*
	* This function checks if the given command has already been loaded.
	* The commands classes are stored under the folder $this->commandsFolder and their name must be:
	* $commandName.command.php
	* They also must extend the class Command, defined in the file Command.class.php
	*
	* @param string commandName - The name of the command to check
	* @return true - In case the given command was already loaded
	* @return false - In case the given command was not loaded yet or the command name is not valid
	*/
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
	
	/*
	* This functions loads the given command into the memory
	* 
	* @param string commandName - the name of the command to be loaded into the memory
	* @return boolean false - In case the command doesn't exists, the class isn't defined
	* @return boolean false - In case the class is not defined in the proper file OR do not extends the class Commands
	* @return boolean true - In case the command was successfully loaded OR was already loaded
	*/
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
	
	/*
	* This function checks if the given command exists
	*
	* @param string commandName - The name of the command to check
	* @return boolean false - In case the given command name is not valid or the file not exists
	* @return boolean true - In case the file exists. NOTE that this doesn't explicitly means the class is properly defined.
	*/
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
	
	/*
	* This functions get's a string containing the params and split's them into an array, specifying each parameter type
	*
	* Each row of the returning array has the following structure:
	* ['type'] => 'integer'||'float'||'string'
	* ['value'] => The parameter value
	*
	* @param string paramsString - The string containing the parameters set to split
	* @return boolean false - In case the given parameter isn't a string
	* @return boolean false - In case it occurs a parse error
	* @return array $separedParams - If the params are successfully divided.
	*/
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
	
	/*
	* This function parses the given command, splitting it's name and params
	* Note that this function doesn't not executes something, instead, use the function executeCommand()
	* 
	* @param string command - The command to parse.
	* @return boolean false - In case the command has any syntax error
	* @return array $commandParts - Returns an array with the command name, and a sub-array with the params (each row -> each value)
	*/
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
	
	/*
	* This function parses the given command, and in case of success, executes it.
	* 
	* @param string command - The command to be executed
	* @return boolean false - In case it occurs any parsing error (see parseCommand() )
	* @return boolean false - In case the command instance doesn't exists
	* @return string $output - Returns the returned output by the command execution
	*/
	public function executeCommand($command){
		$commandParts = $this->parseCommand($command);
		if ($commandParts === false){
			return(false);
		}
		
		if ($this->autoHelp == true and $commandParts['name'] == 'help'){
			$commandInstance = $this->loadCommand($commandParts['params'][0]);
			
			if ($commandInstance === false){
				return(false);
			}
		
			$commandInstance = &$this->commandsInstances[$commandParts['params'][0]];
			
			
			if (method_exists($commandInstance, 'getHelp')){
			
				$output = 'Help for '.$commandParts['params'][0].': '.call_user_func(array(&$commandInstance, 'getHelp'));
			} else {
				$output = 'The command doesn\'t provides any help.';
			}			
		} else {
			$commandInstance = $this->loadCommand($commandParts['name']);

			if ($commandInstance === false){
				return(false);
			}

			$commandInstance = &$this->commandsInstances[$commandParts['name']];

			$output = call_user_func_array(array(&$commandInstance, 'execute'), $commandParts['params']);
		}
		
		return($output);
	}
	
	
	public function __construct($commandsFolder, $autoHelp = true){
		
		$this->regexMatch = '@^/([0-9a-zA-Z]*)((?:(?: )+(?:"[^"\\\r\n]*(?:\\.[^"\\\r\n]*)*"|[+-](?:(?:\b[0-9]+)?\.)?[0-9]+\b|[0-9a-zA-Z])+)+)?$@';
		$this->autoHelp = (boolean)$autoHelp;
		
		if (file_exists($commandsFolder) and is_dir($commandsFolder)){
			$this->commandsFolder = $commandsFolder;
		}
	}
	
}



?>