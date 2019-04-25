<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<pre>

<?php

header('Content-Type: text/html; charset=utf-8');
ini_set('error_reporting', E_ALL)							;
ini_set('default_charset', 'utf-8')							;
ini_set('display_errors', 1)								; 
ini_set('display_startup_errors', 1)						;
ini_set('memory_limit', '348M')  							;
ini_set('max_execution_time', 3000)							; //300 seconds = 5 minutes

$POSITION_B_DATE = null;
$POSITION_C_DATE = null;

require_once __DIR__ . '/src/SimpleXLS.php'	; // подключаем класс для парсинга xlsx

/*  === тут подключаем библиотеку для bx  ===*/
require_once(__DIR__ . '/bx_api/transferData.php'   ); // CURL
require_once(__DIR__ . '/bx_api/userField.php'      ); // Создание пользовательских полей
require_once(__DIR__ . '/bx_api/essence.php'        ); // Создание лида

$webHookScript = 'https://b24-zlep0n.bitrix24.ru/rest/1/7zoaaa611dmc0ze9/';

$userfield  = 	new userField($webHookScript)	;
$eseence	=   new essenceAdd($webHookScript)	;


/*  === ################  ===*/


function parceDateXLS($date){
	$UNIX_DATE = ($date - 25569) * 86400;
	return gmdate("d.m.Y", $UNIX_DATE);
}
function getColumn($item , $nameArr){
	/* тут ищем какой столбец как называется */
	foreach($nameArr as $name){

		$item = preg_replace('/\s+/', '', $item); // удалим все пробелы что-бы не мешали искать

		//приводим строку к нижнему регистру и пытаемся найти в ней совпадение
		if(stristr(mb_strtolower($item), $name[0])){
			$result =  $name[1];
			echo $item . ' ' . $name[0] . '<br>';
			break;
		}else{
			$result = false;
			continue;
		}
	}
	if($result){
		return $result;
	}
}

function returnColumnAdres($row , $ardessArr){
    print_r($row);
    die();
    $result = array();

    foreach ($row as $rowItem){
        $i = 0;
        foreach ($ardessArr as $item) {
            $i++;
            $rowItem = preg_replace('/\s+/', '', $rowItem); // удалим все пробелы что-бы не мешали искать
            if(stristr(mb_strtolower($rowItem), $item)){
                echo "$item <hr>";
                $result[] = $i;
            }
        }
    }
    $str = null;
    foreach ($result as $item) {
        $str .= ' ' . $row[$item];
    }
    echo  "<hr>$str<hr>";
    return $result;
}

$path			= __DIR__ . '/price/краснодар2.xls'  		; // путь к файлу идеального прайса
$file 			= SimpleXLS::parse($path)					; // прайс поставщика

// список столбцов
$inn 				= ['инн'				, 'UF_CRM_5ABA93EC2EDE8'	];
$lastName 			= ['фами'				, 'UF_CRM_1538639887'		];
$firstName  		= ['имя'				, 'UF_CRM_1538639857'		];
$faherName  		= ['отче'    			, 'UF_CRM_1538639906'		];
$pone       		= ['телеф'				, 'PHONE'					];
$dateBufday 		= ['датарождения'		, 'UF_CRM_1549955139'    	];
$adress     		= ['адр'				, 'UF_CRM_5AE35429E0C96'	];
$email      		= ['mail'				, 'EMAIL'					];
$OGRN       		= ['огрнюл'				, 'UF_CRM_5ABA93EC372EF'    ];
$dateAdd_OGRN  		= ['регистрацииюл'   	, 'UF_CRM_5ABA93EC40DEF'    ];
$companyNameOOO     = ['наименованиеюл'	    , 'TITLE'    				];

// возможные стодбцы для адреса
$ADRES_postIndex	= 'Индекс'	;
$ADRES_house	    = 'дом'	    ;
$ADRES_corpus	    = 'корпу'	;
$ADRES_apartment    = 'кв'		;
$ADRES_region	    = 'регион'	;
$ADRES_area		    = 'рай'	    ;
$ADRES_city		    = 'город'	;
$ADRES_locality	    = 'пунк'	;
$ADRES_street 	    = 'ул'		;

$nameArr     = [$inn,
				$lastName,
				$firstName,
				$faherName,
				$pone,
				$dateBufday,
				$adress,
				$email,
				$OGRN,
				$dateAdd_OGRN,
				$companyNameOOO
			]; 
$adressParam = [
				$ADRES_postIndex,
                $ADRES_region,
                $ADRES_city,
                $ADRES_locality,
                $ADRES_area,
				$ADRES_street,
                $ADRES_corpus,
				$ADRES_house,
				$ADRES_apartment,
			];

if ($file) {

	$rows = $file->rows(); // все строки
	$arrNameColumn 		= []	; // резервируем массив под значения столбцов
	$flag_make_adres	= false	; // флаг искать адрес по кусочкам или уже есть столбец "адрес"

	/* (крутим только заголовки таблицы) тут ищем какой столбец как называется и какие данные сохранять */
	foreach( $rows as $row){
		foreach($row as $item){	
			//echo $item . '<br>';
			$arrNameColumn[] = getColumn($item, $nameArr);
		}

		if(in_array('UF_CRM_5ABA93EC372EF',$arrNameColumn)){
			$flag_make_adres = false;
		}else{
			$flag_make_adres = true;
		}

		break;
	}

	/* крутим тело таблицы  
	   по одной СТРОКЕ*/
	$i=0;

	$arrCompany = array(); 		  // массив для добавления компании

	foreach( $rows as $row){
		$i++;
		if($i == 0){  continue;} // проскачим первую итерацию с заголовками

		// крутим по одной ячейке
		for($a = 0; $a <= count($row)-1; $a++){

		    /* тут логика создания строки адреса */
            /* если нет столбца  "адрес"*/
			if($flag_make_adres){
				for($q = 0; $q <= count($row)-1; $q++){
					 
				}	
			}
            print_r(returnColumnAdres($row, $adressParam));
			die();

            // пустые строки пропустим
			if($arrNameColumn[$a] == null){continue;}
			
			if($arrNameColumn[$a] == 'UF_CRM_5ABA93EC40DEF' || $arrNameColumn[$a] == 'UF_CRM_1549955139'){
				$val = parceDateXLS($row[$a]);
			}else{
				$val = $row[$a];
			}

			$arrCompany[$i][$arrNameColumn[$a]] = $val ;
		}

	}

	print_r($arrCompany);
    die();
    foreach ( $arrCompany as $item) {
        usleep(600);
        //print_r($item);
        var_dump($eseence->companyAdd($item))  ;
	}


	//print_r($arrCompany);
	memory_get_peak_usage();

	function formatBytes($bytes, $precision = 2) {
		$units = array("b", "kb", "mb", "gb", "tb");

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$bytes /= (1 << (10 * $pow));

		return round($bytes, $precision) . " " . $units[$pow];
	}

} else {
	echo SimpleXLSX::parse_error();
}



$memory = formatBytes(memory_get_peak_usage());
echo 'Памяти затрачено :'. $memory;
?>
</pre>