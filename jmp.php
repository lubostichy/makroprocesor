<?php

#JMP:xtichy23

////////////////////////// PARAMETERS //////////////////////////

if (($argc == 2) and ($argv[1] == '--help')) { // zavola napovedu
	fwrite(STDOUT, "--help vypise pomocnika\n--input=filename zadaný vstupní textový soubor\n--output=filename textový výstupní soubor\n--cmd=text text bude před začátkem zpracování vstupu\n-r redefinice\n");
	exit(0);
	//return 0;
}
elseif ($argc <= 5) {
	$rightcount = 0;
	foreach ($argv as $arg) {
		
		if ( preg_match("/^--input=.*/", $arg) ) {	// spracovanie --input
			if ( is_null($input) ){
				$input = substr($arg, 8);
				$rightCount++;
			}
			else {
				wrongParams();
			}
		}
		if ( preg_match("/^--output=.*/", $arg) ) {  // spracovanie --output
			if ( is_null($output) ) {
				$output = substr($arg, 9);
				$rightCount++;
			}
			else {
				wrongParams();
			}
		}
		if ( preg_match("/^--cmd=.*/", $arg) ) {  // spracovanie --cmd
			if ( is_null($cmd) ) {
				$cmd = substr($arg, 6);
				$rightCount++;
			}
			else {
				wrongParams();	
			}
		}
		if ( $arg == "-r" ) {			// spracovanie -r
			if ( is_null($redef) ) {
				$redef = true;
				$rightCount++;
			}
			else {
				wrongParams();
			}
		}
	}
}
else {
	wrongParams();
}


if ( $rightCount != ($argc - 1) ) { // osetrenie vyskytu navyse parametrov
	wrongParams();
}

function wrongParams(){
	//fwrite(STDERR, "Wrong parameters!\n");  // nespravne zadane parametre
	exit(1); 
}

////////////////////////// END OF PARAMETERS //////////////////////////


if ( !is_null($input) and !file_exists($input) ) {  // nespravny vstupny subor
	//fwrite(STDERR, "Wrong input file!\n");
	exit(2);
}

if (!is_null($input)) {   // rozhodne o vstupnom subore
    if (($inputFile = fopen($input, 'r')) == false) {
        //fwrite(STDERR, "Permission denied!\n");
        exit(3);
    }
} else {
    $inputFile = STDIN;
}


if (!is_null($output)) {   // rozhodne o vystupnom subore
    if (($outputFile = @fopen($output, 'w')) == false) {
        //fwrite(STDERR, "Permission denied!\n");
        exit(3);
    }
} else {
    $outputFile = STDOUT;
}

$index = '-1';
$outputText[0] = "";

global $text;

$i=0;
while (false !== ($char = fgetc($inputFile))) {
	
	$text[$i] = $char;
	$i++;
}



if ( !is_null($cmd) ) {
	$text = array_merge(str_split($cmd), $text);  // text vlozeny pred vstup
}	


// struktura makra
class ObjMacro{	
    
    public $name;       // nazov makra
    public $body;       // telo makra
    public $args;       // pole argumentov
    public $countArgs;  // pocet argumentov

}

$count = -1; // premenna pre pocet makier

////////////////////////// @def //////////////////////////

function defMacro($arraySliced) {
    
        global $obj, $count, $redef, $SPACES;        
        
        $keykey = 0;
        if (ctype_space($arraySliced[$keykey])) {
            if (strcmp($SPACES, "ON") == 0) { // makro @set
                return 0;
            } 
            else {
                while(ctype_space($arraySliced[$keykey])) {
                    $keykey++;
                }
            }
        }
	if ($arraySliced[$keykey] == '@') {
		$keykey++;
		if ((ctype_alpha($arraySliced[$keykey])) or ($arraySliced[$keykey] == '_')) {
			$i = 0;
			while((ctype_alpha($arraySliced[$keykey])) or (is_numeric($arraySliced[$keykey])) or ($arraySliced[$keykey] == '_')) {
					$arrayMacro[$i] = $arraySliced[$keykey];
					$i++;
					$keykey++;
			}
                        
                        $nameOfMacro = implode('', $arrayMacro);  // vytvori string z pola charov
			$arrayMacro = array_fill(0, 100, '');
			$keykey--;

                        $i = 0;
                        while (($nameOfMacro != $obj[$i]->name) and ($i <= $count)) {                            
                            $i++;
                        }                        
                        if (($nameOfMacro == "__def__") or ($nameOfMacro == "__let__") or ($nameOfMacro == "__set__")) {
                            return 57;
                        }
                        
                        if ($nameOfMacro == $obj[$i]->name) {
                            $iMacro = $i;
                            if ($redef) { // je parameter -r
                                return 57;                                
                            }
                            $obj[$iMacro]->countArgs = 0;
                        }
                        else {                            
                            $count++;
                            $iMacro = $count;
                            $obj[$count] = new objMacro;
                            $obj[$count]->name = $nameOfMacro;
                            $obj[$count]->countArgs = 0;
                        }
                }
        
                        
                        $keykey++;                        
			//macro sa nenaslo a vytvori sa nove alebo sa makro naslo a moze sa predefinovat:
                        // spracuje argumenty:
                        if (ctype_space($arraySliced[$keykey])) {
                            if (strcmp($SPACES, "ON") == 0) { // makro @set
                                return 0;
                            } 
                            else {
                                while(ctype_space($arraySliced[$keykey])) {
                                    $keykey++;
                                }
                            }
                        }
                        
                        if ($arraySliced[$keykey] == '{') {
                            
                            $keykey++;
                            while ($arraySliced[$keykey] != '}') {
                                
                                if ($arraySliced[$keykey] == '$') {     // nacitanie nazvu argumentu: [a-zA-Z_][a-zA-Z0-9_]*
                                    $keykey++;
                                    
                                    if ((ctype_alpha($arraySliced[$keykey])) or ($arraySliced[$keykey] == '_')) {   
                                        
                                        $tmpArr = array_fill(0, 30, '');    // vyprazdni pomocne pole
                                        $i = 0;
                                        $tmpArr[$i] = $arraySliced[$keykey];
                                        $keykey++;
                                        while((ctype_alpha($arraySliced[$keykey])) or (is_numeric($arraySliced[$keykey])) or ($arraySliced[$keykey] == '_')) {
                                            $i++;
                                            $tmpArr[$i] = $arraySliced[$keykey];  
                                            $keykey++;
                                        }
                                        $tmpArg = implode('',$tmpArr); // argument makra                                        
                                        $obj[$iMacro]->args[$obj[$iMacro]->countArgs] = '$'.$tmpArg; // prida "argument" do pola argumentov makra
                                        $obj[$iMacro]->countArgs++; // pocet argumentov makra
                                        
                                        if (ctype_space($arraySliced[$keykey])) {
                                            $keykey++;    // ak je biely znak tak preskoci a pokracuje na dalsi argument 
                                        }
                                    } else {        // znak za $ nesmie byt iny ako je povoleny
                                        $error = 1;
                                        return;
                                    }
                                } elseif (ctype_space($arraySliced[$keykey])) {                                    
                                    $keykey++;  // preskoci biely znak
                                } else {
                                    $error = 1;
                                    return; // inak je tam neziaduci znak
                                }
                            }
                        
                            
			
                        }
                        
                        // dokoncilo sa spracovanie argumentov a ide na spracovanie tela makra:
                        $keykey++;
                        
                        // osetrenie bielych znakov
                        //nacitanie tela makra
                        $i = 0;
                        $tmpBody[$i] = '';
                        if ($arraySliced[$keykey] == '{') {
                            if ($arraySliced[$keykey] != '{') {
                                $tmpBody[$i] = $arraySliced[$keykey];
                                $i++;
                            }                            
                            $keykey++;
                            
                            $last_key = key( array_slice( $arraySliced, -1, 1, TRUE ) );
                            while ($arraySliced[$keykey] != '}') {
                                $tmpBody[$i] = $arraySliced[$keykey];
                                $i++;
                                $keykey++;
                                if ($arraySliced[$keykey] == '@') {
                                    // escapovanie
                                }
                                if (($keykey == $last_key) and ($arraySliced[$keykey] != '}')) {
                                    $error = 1;
                                    break;
                                }
                            } 
                            if ($arraySliced[$keykey] != '}') {
                                $tmpBody[$i] = $arraySliced[$keykey];
                            }
                            
                        }
                        $body = implode('',$tmpBody);
                        $obj[$iMacro]->body = $body;    // {telo makra}
                        //echo 'X'.$obj[$iMacro]->body.'XX';
                if ($error == 1) {
                    return 0;
                }

                return $keykey;                
	}
        
        return 0;
}

////////////////////////// @let //////////////////////////

function letMacro($arraySliced) {
    
        global $redef, $obj, $count;
        
        if ($redef) {
            return 57;
        }
	$pattern = '/@[a-zA-Z_][a-zA-Z0-9_]*@[a-zA-Z_][a-zA-Z0-9_]*/';	// regularny vyraz
	$subject = implode('',$arraySliced);	// spoji pole do stringu
    $matches = NULL;
	preg_match($pattern, $subject, $matches);	// najde regularny vyraz
	$abMacras = explode('@', $matches[0]);
	$cutMatches = implode('', $matches);
	if ($abMacras[1] == null and $abMacras[2] == null) {
            return 1;
        }
	
	$aMacro = $abMacras[1];		// 1. makro
	$bMacro = $abMacras[2];		// 2. makro

	$nextkey = (strlen($aMacro) + strlen($bMacro) + 1); // +1, nie +2 
	$cutString = substr($subject, 0, ($nextkey + 1));	// +2

	if (strcmp($cutString, $cutMatches) != 0) {
		return 1;
	}
        
        if (($aMacro == '__def__') or ($aMacro == '__let__') or ($aMacro == '__set__')) {
            return 57;
        }

	if ($aMacro == 'null') {
		return $nextkey;
	}	

	if ($bMacro == 'null') {
		
                // najde makro A
                $i = 0;
                while ($i < $count) {
                    if (strcmp($obj[$i]->name, $aMacro) == 0) {
                        break;
                    }
                    $i++;
                }
                
                //vymaze makro A
                if (strcmp($obj[$i]->name, $aMacro) == 0) {
                    $obj[$i]->name = "";
                    $obj[$i]->body = "";
                    $j = 0;
                    while ($j <= $obj[$i]->countArgs) {
                        $obj[$i]->args[$j] = "";
                    }
                    
                }
                
		return $nextkey;
	}
    
    // najde makro A
    $i = 0;
    while ($i < $count) {
        if (strcmp($obj[$i]->name, $aMacro) == 0) {
            break;
        }
        $i++;
    }

    // makro je najdene a najde sa makro b, ktore sa priradi k makra a
    if (strcmp($obj[$i]->name, $aMacro) == 0) {
        $j = 0;
        while ($j < $count) {
            if (strcmp($obj[$j]->name, $bMacro) == 0) {
                break;
            }
            $j++;
        }
        if (strcmp($obj[$j]->name, $bMacro) == 0) {
            // a := b;
            $obj[$i]->name = $obj[$j]->name;
            $tmp = 0;
            while ($tmp < $obj[$j]->countArgs) {
                $obj[$i]->args[$tmp] = $obj[$j]->args[$tmp];
                $tmp++;
            }
            $obj[$i]->body = $obj[$j]->body;
        }
    }

        // findMacro
	// inak prirad makro b k makru a


	return $nextkey; // pokracovat za makrom let
}

////////////////////////// @set //////////////////////////

function setMacro($arraySliced) {
        global $SPACES;
	for ($i = 1; $i <= 15; $i++) {
		$arrayMacro[$i] = $arraySliced[$i];		
	}
	$set = implode('',$arrayMacro);
	$arrayMacro = array_fill(0, 100, '');

	if ((strcmp($set, "{-INPUT_SPACES}") != 0) and (strcmp($set, "{+INPUT_SPACES}") != 0)) { // nerovnaju sa
		return 1;
	}
        
        if (strcmp($set, "{-INPUT_SPACES}") == 0) {
            $SPACES = "OFF";
        }
        else if (strcmp($set, "{+INPUT_SPACES}") == 0) {
            $SPACES = "ON";
        }
	return 15; // dlzka bloku set
}

////////////////////////// macro expansion //////////////////////////

function expansion($arraySliced, $macro) {
    global $obj, $count, $outputFile;

    $i = 0;
    while (strcmp($obj[$i]->name, $macro) != 0) {   //najde makro
        if ($i == $count) {            
            break;
        }
        $i++;
    }
    
    if (strcmp($obj[$i]->name, $macro) == 0) {      //makro sa naslo a expanduje sa
        $iMacro = $i;        
    }
    else {
        return 56; // makro neexistuje
    }
    $i = 0;
    $keykey = 1;
   
    while ($i < $obj[$iMacro]->countArgs) {
        if ($arraySliced[$keykey] == '{') {     // ak je hodnota v bloku
            $j = 0;     // index znaku v makre
            while (1) {
                $keykey++;
                if ($arraySliced[$keykey] == '}') {
                    $keykey++;
                    break;
                } 

                $tmp[$j] = $arraySliced[$keykey];
                $j++;
                
            } 
            $tmp = implode('',$tmp);
            $tmpArg[$i] = $tmp;         // prida celu hodnotu z bloku medzi hodnoty pre telo makra     
            $i++;
            continue;            
        }
        $tmpArg[$i] = $arraySliced[$keykey];  // vytvorim pole hodnot pre telo makra
        $i++;
        $keykey++;        
    }
    //zisti argumenty, musia byt v spravnom pocte
    // dosadi do tela a telo vypise, 0 az vsetky argumenty mozu byt dosadene jeden az viackrat

    $tmpBody = str_replace($obj[$iMacro]->args, $tmpArg, $obj[$iMacro]->body);
    fwrite($outputFile, $tmpBody);
    return $keykey-1;
    
}

////////////////////////// check brackets //////////////////////////

function checkBrackets($arraySliced) {                         
			$leftCurlyBrackets = 1; // {
			$rightCurlyBrackets = 0; // }

			$last_key = key( array_slice( $arraySliced, -1, 1, TRUE ) );
                        
                        $keykey = 0; 
			while ( ($leftCurlyBrackets != $rightCurlyBrackets) ) {  // overenie poctu zatvoriek
				$keykey++;

				if ($arraySliced[$keykey] == '@') {		// aby nebralo zatvorky za @
					$keykey++;					
					if ($keykey == $last_key) { 
						break;
                                        }
					if ( ($arraySliced[$keykey] == '{') or ($arraySliced[$keykey] == '}') ) {
						continue;
                                        }
				}
						
                                if ($arraySliced[$keykey] == '{') {
                                    $leftCurlyBrackets++;
                                }
                                if ($arraySliced[$keykey] == '}') {
                                    $rightCurlyBrackets++;	
                                }

				if ($keykey == $last_key) {
					break;
                                }
                                //echo "L:".$leftCurlyBrackets."R:".$rightCurlyBrackets;

			}

			if ($leftCurlyBrackets != $rightCurlyBrackets) {                            
                            return 0;
                        }
                        
                        return $keykey;
}

////////////////////////// READING //////////////////////////

$SPACES = "ON"; // ON - berie do uvahy biele znaky, OFF - ignoruje biele znaky
$key = 0;
while ($key < count($text)) { 
	switch ($text[$key]) {
		case '@': {
			$key++;
			
			if ( ctype_alpha($text[$key]) ) {		// regularny vyraz ako nazov makra:
				$i = 0;
				while ( ctype_alpha($text[$key]) or is_numeric($text[$key]) ){						
					$arrayMacro[$i] = $text[$key];
					$i++;
					$key++;	
				}				
				$Macro = implode('', $arrayMacro);  // vytvori string z pola charov
				$arrayMacro = array_fill(0, 100, '');
				$key--; // aby sme nestratili znak za nazvom makra
				switch ($Macro) {
					case "def": {
                                                $key++;
                                                $arraySliced = array_slice($text, $key);
						$nextkey = defMacro($arraySliced);
                                                if ( $nextkey == 0) {
                                                    //fwrite(STDERR, "Syntax error!\n");
                                                    exit(56);
                                                }
                                                if ($nextkey == 57) {
                                                    //fwrite(STDERR, "Invalid redefinition!\n");
                                                    exit(57);
                                                }
                                                $key = $key + $nextkey;
						break;
					}

					case "null":
						//ja som null;
						break;

					case "let": {		
						$key++;
                                                $arraySliced = array_slice($text, $key);
						$nextkey = letMacro($arraySliced);					
						if ($nextkey == 1) {
                                                    //fwrite(STDERR, "Syntax error!\n");
                                                    exit(56);
						}
                                                if ($nextkey == 57) {
                                                    //fwrite(STDERR, "Invalid redefinition!\n");
                                                    exit(57);
                                                }
						$key = $key + $nextkey;
						break;
					}

					case "set": {						
						$arraySliced = array_slice($text, $key);
						$nextkey = setMacro($arraySliced, $key);
						if ($nextkey == 1) {
							//fwrite(STDERR, "Semantic error!\n");
							exit(56);
						}
						$key = $key + $nextkey;
						break;
					}

					default : { 
                                            
                                                // expanzia makra:
                                                $arraySliced = array_slice($text, $key);
                                                $nextkey = expansion($arraySliced, $Macro);
                                                if ($nextkey == 55) {
                                                    //fwrite(STDERR, "Syntax error!\n");
                                                    exit(55);
                                                }
                                                if ($nextkey == 56) {
                                                    //fwrite(STDERR, "Semantic error!\n");
                                                    exit(56);
                                                }
                                                $key = $key + $nextkey;	
                                               
						break;
                                        }
				}

				break;
			} else {					
				if ( ($text[$key] == '@') or ($text[$key] == '{') or ($text[$key] == '}') or ($text[$key] == '$') ) {
					fwrite($outputFile, $text[$key]); // @@ -> @ @{ -> { @} -> } @$ -> $						
				} else {
					//fwrite(STDERR, "Syntax error!\n");
					exit(55);
					
				}
			}
			 
			break;	
			}

			
		case '{': {
                        
                    	$keyBracket = $key;		// zapamata si poziciu pred nacitanim bloku
                           
                        $arraySliced = array_slice($text, $key);
                        $lastBracket = checkbrackets($arraySliced); // vrati poslednu poziciu s '}'
                        if ($lastBracket == 0){
                            	//fwrite(STDERR, "Syntax error!\n");
				exit(55);                                
                        }			
			else {
				$lastBracket = $key + $lastBracket;    // pokracuje za blokom   
				$key = $keyBracket;	// navrat na poziciu pred nacitanim bloku
				$key++;			// znak po prvej '{'
				while ($lastBracket != $key) {
					if ($text[$key] == '@') {
						$key++;
						if ( ($text[$key] == '@') or ($text[$key] == '{') or ($text[$key] == '}') ) {
						} else{
							$key--;
                                                }
					}
					fwrite($outputFile, $text[$key]);
					$key++;
				}
			}
			break; 
		} 
		case '}' : {
			//fwrite(STDERR, "Syntax error!\n");
			exit(55);
			}

		case '$': {
			//fwrite(STDERR, "Syntax error!\n");
			exit (55);			
		}
		default: {                        
                        if (ctype_space($text[$key]) && (strcmp($SPACES, "OFF") == 0)){                         
                            break;                                
                        }
			fwrite($outputFile, $text[$key]); // iny znak                        
			break;
                }
        }
		
	$key++;
}


fclose($outputFile);
fclose($inputFile);

?>