<?php

//функция отправки команд на кассу
function getUrl($url, $post_string=false) {
        $adress='http://office.armax.ru:58088/';//это адрес тестовой кассы. Для боевой кассы указываем ip адрес. На роутере настраиваем переадресацию портов на локальный соответствующий ip адрес и порт 8088
        $login='99';//Для боевой кассы зарегистрированной на umka365 указываем номер телефона
        $pass='99';//Для боевой кассы зарегистрированной на umka365 указываем пароль кассира
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, $adress.$url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if($post_string != "") { 
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string); 
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type: application/json',                                                                                
                'Content-Length: ' . strlen($post_string))                                                                       
            );
        }
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $login.":".$pass); 
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; rv:12.0) Gecko/20100101 Firefox/24.0");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $content = curl_exec ($ch);
        curl_close ($ch);
        return $content;
}

//Функции для работы с БД
function getQuery($query){
global $link;

  $res = mysqli_query($link,$query);
  $row = mysqli_fetch_row($res);
  $var = $row[0];
  return $var;
}
 
function setQuery($query){
global $link;
  $res = mysqli_query($link,$query);
  return $res;
}
 
//Соединяемся с базой
$link=mysqli_connect('localhost:3306', 'root', 'root');//3306 указывается на локальной сервере MAMP
if (mysqli_connect_error()) {
    die('Connect Error ('.mysqli_connect_errno().') '.mysqli_connect_error());
}


//устанавливаем часовой пояс сервера
date_default_timezone_set('Asia/Yekaterinburg');


//Пишем в лог запросы
$fd = fopen("logs.txt", 'a+') or die("не удалось открыть файл ");
fwrite($fd, date("Y-m-d h:m:s"));
foreach ($_POST as $key => $value) {
    fwrite($fd, " "."$key".'='."$value");    
}
fwrite($fd, "\r\n");    
fclose($fd);


if (isset($_POST["kassa"])) {
    //$clientId='';//в случае обращения к боевой кассе зарегистрированной на umka365 обязательно указать. Обычно это заводской номер кассы
    switch($_POST["kassa"]){
        case "check_status"://запрашиваем статус кассы
            if (isset($clientId)) {
                $kktUrl='cashboxstatus.json?clientId='.$clientId;
            } else {
                $kktUrl='cashboxstatus.json';
            }
            $answer1=getUrl($kktUrl, '');
            $answer=json_decode($answer1,true);
            $waiting_ofd=getQuery("SELECT count(`id`) FROM `kassa`.`onlinekassa` WHERE `onOFD`='wait'");//смотрим в БЗ, есть ли документы, у которых нет статуса отправки в ОФД
            if ($waiting_ofd>0 and isset($answer['cashboxStatus']['fsStatus']['transport']['state']) and $answer['cashboxStatus']['fsStatus']['transport']['state']==0) {
                if ($answer['cashboxStatus']['fsStatus']['transport']['state']==0) {//Если это поле имеет значение 0, значит нет документов, ожидающих отправки в ОФД. Проставляем статус отправки в ОФД=ОК в БД
                    setQuery("UPDATE `kassa`.`onlinekassa` SET `onOFD`='OK' WHERE `onOFD`='wait'");
                }
            }
            echo json_encode($answer1);
        break;
        
        case "checkinfo"://запрашиваем данные по фискальному документу
            if (isset($clientId)) {
                $kktUrl='fiscaldoc.json?number='.$_POST['fn'].'&print=0&clientId='.$clientId;
            } else {
                $kktUrl='fiscaldoc.json?number='.$_POST['fn'].'&print=0';
            }
            $answer=getUrl($kktUrl, '');
            echo json_encode($answer);
        break;
        
        case "create_document"://создаем фискальный документ
            //Сначала запрашиваем статус кассы на предмет выяснения статуса смены.
            $readyfordocument=false;//статус готовности кассы к приему запросов
            if (isset($clientId)) {
                $kassa_status=json_decode(getUrl('cashboxstatus.json?clientId='.$clientId, ''),true);
            } else {
                $kassa_status=json_decode(getUrl('cashboxstatus.json', ''),true);
            }
            
            if ($kassa_status==false) {
                //касса не ответила - выходим
                echo json_encode(array("status"=>"error","message"=>'Касса не отвечает. Нужно делать новый запрос с ТЕМ ЖЕ sessionID'));
                break;
            }
            
            if(is_array($kassa_status)) {
                if(isset($kassa_status['cashboxStatus']['fsStatus']['cycleIsOpen']) and $kassa_status['cashboxStatus']['fsStatus']['cycleIsOpen'] == 1) {
                    $smena = 2;//смена открыта
                    $readyfordocument=true;
                } else {
                    $smena = 1;//смена закрыта. Открываем ее
                    $open_smena=json_decode(getUrl('cycleopen.json?print=0&clientId='.$clientId, ''),true);
                    if (isset($open_smena['document']['result']) and $open_smena['document']['result']==0) {
                        $readyfordocument=true;
                    } else {
                        echo json_encode(array("status"=>"error","message"=>'Не могу открыть смену на кассе. Проверьте статус кассы. sessionID можно не менять'));
                        break;
                    }
                    
                }
            } else {
                //касса ответила, но ответ непонятен - выходим
                echo json_encode(array("status"=>"error","message"=>'Ответ кассы непонятен. Проверьте состояние кассы. sessionID можно не менять'));
                break;
            }

            if ($readyfordocument) {          
               $sessionId=$_POST['id_paym'];//Уникальное ИД сессии (генерируется самостоятельно и должно быть уникальным для каждого чека). Я использую идентификатор платежа, формируемый в системе.
               $request['document']['sessionId'] = $sessionId;
            
               # Флаг необходимости печати чека
               $request['document']['print'] = 0;
               $request['document']['data']['docName'] = 'Кассовый чек';
            
               # Тип документа (1. Продажа, 2.Возврат продажи, 4. Покупка, 5. Возврат покупки, 7. Коррекция прихода, 9. Коррекция расхода)
               $request['document']['data']['type'] = 1;
               if ((int)$_POST['tag1054']==2) {
                  //меняем параметры для Возврата прихода
                  $request['document']['data']['type'] = 2;
               }
            
               # ТИП ОПЛАТЫ (1. Наличным, 2. Электронными, 3. Предоплата, 4. Постоплата, 5. Встречное предоставление)
               $request['document']['data']['moneyType'] = (int)$_POST['moneyType'];
            
               # Сумма закрытия чека (может быть 0, если без сдачи) в копейках
               $request['document']['data']['sum'] = $_POST['summ']*100;
            
               $request['document']['result'] = 0;
        
               # применяемая система налогообложения (применяется битовое значение) См. (номер бита - значение)
               # (0 - 1) - ОСН, (1 - 2) - УСН доход, (2 - 4) - УСН доход - расход, (3 - 8) - ЕНВД, (4 - 16) - ЕСН, (5 - 32) - Патент
               $request['document']['data']['fiscprops'][] = array('tag' => 1055, 'value' => 2);
            
               # регистрационный номер ККТ (20 символов, до установленной длины дополняется пробелами справа)
               # Берется из регистрационных данных в ФН. Если передавать в чеке, то чек будет оформлен 
               # только при совпадении переданного РНМ и РНМ, с которым касса зарегистрирована
               //$request['document']['data']['fiscprops'][] = array('tag' => 1037, 'value' => "0000000001020321");
        
               #сумма по кассовому чек/(БСО) электронными 
               #Величина с учетом копеек, печатается в виде числа с фиксированной точкой (2 цифры после точки) в рублях - налоговая 
               #Обязательно передавать только при использовании нескольких типов оплат. Передается в копейках. - касса 
               //$request['document']['data']['fiscprops'][] = array('tag' => 1081, 'value' => $_POST['summ']);
            
               # ИНН пользователя
               #Берется из регистрационных данных в ФН. Если передавать в чеке, то чек будет
               #оформлен только при совпадении переданного инн и инн, с которым касса зарегистрирована
               //$request['document']['data']['fiscprops'][] = array('tag' => 1018, 'value' => $_POST['inn']);
            
               # признак расчета. 1 - <ПРИХОД>, 3 - <РАСХОД>, 2 - <ВОЗВРАТ ПРИХОДА>, 4 - <ВОЗВРАТ РАСХОДА>
               $request['document']['data']['fiscprops'][] = array('tag' => 1054, 'value' => (int)$_POST['tag1054']);
            
               # телефон или электронный адрес покупателя
               $request['document']['data']['fiscprops'][] = array('tag' => 1008, 'value' => $_POST['email']);
            
               #адрес сайта ФНС
               $request['document']['data']['fiscprops'][] = array('tag' => 1060, 'value' => 'nalog.ru');
            
               #email отправителя чека
               #Передавать не нужно. Берется из регистрационных данных в ФН.
               //$request['document']['data']['fiscprops'][] = array('tag' => 1117, 'value' => '1@sfv.rv');
            
               //Выше заполнили общую информацию. Теперь необходимо заполнить данные по проданным товарам/услугам. 
               //Для этого мы передаем в эту функцию переменную $_POST['sales'], в которой содержится массив с данными по позициям.
            
               if ((int)$_POST['tag1054']==2) {
                   #если у нас возврат прихода заполняем тег 1192
                   $request['document']['data']['fiscprops'][] = array('tag' => 1192, 'value' => $_POST['tag1192'], "printable"=>$_POST['tag1192']);
               }
            
               $summ_total=0;//переменная для проверки сходимости общей суммы чека и суммы по позициям. ВНИМАНИЕ: ВОЗМОЖНО ТРЕУБЕТ ИЗМЕНЕНИЯ ФОРМУЛ ПРИ НДС!=0
               $sales_data=array();
               $sales_data=json_decode($_POST['sales'],true);
            
               for ($x=0;$x<count($sales_data);$x++) {
                   $request['document']['data']['fiscprops'][]=array('fiscprops'=>
                       array(
                          array('tag' => 1214, 'value' => 1),
                          array('tag' => 1212, 'value' => 4),
                          array('tag' => 1030, 'value' => $sales_data[$x]['name']),
                          array('tag' => 1079, 'value' => $sales_data[$x]['price']*100),
                          array('tag' => 1023, 'value' => (string)round($sales_data[$x]['quantity'],3)),
                          array('tag' => 1199, 'value' => $sales_data[$x]['nds_type']),
                          array('tag' => 1043, 'value' => $sales_data[$x]['summ']*100),                 
                       ),
                       'tag' => 1059
                    );
                    $summ_total=$summ_total+$sales_data[$x]['summ']*100;
                    if ($sales_data[$x]['summ']*100 != $sales_data[$x]['price']*100*$sales_data[$x]['quantity']) {
                        echo json_encode(array("status"=>"error","message"=>'Некорректные суммы в позиции '.$sales_data[$x]['name']));
                        break 2;
                    }

                    //Комментарии:
                    #реквизит 1214 "признак способа расчета" (1-Предоплата 100%). Остальные значения смотри в приказе ФНС http://www.consultant.ru/document/cons_doc_LAW_214339/731d2f8d127e3614422af34b4ac197612bd2f64d/
                 
                    #реквизит 1212 "признак предмета расчета" (4 - Услуга). Остальные значения смотри в Приказе ФНС http://www.consultant.ru/document/cons_doc_LAW_214339/cfdfbc0cb69618bfb358a7d612a1ac60149a7525/
                  
                    #реквизит 1030 "наименование предмета расчета". Основания для включения и значения смотри в Прказе ФНС http://www.consultant.ru/document/cons_doc_LAW_214339/6819e270e2b092e9b1927443534cbb840429e19d/
                
                    #реквизит 1079 "Цена за единицу предмета расчета с учетом скидок и наценок". Основания для включения и значения смотри в разъяснениях ФНС http://www.consultant.ru/document/cons_doc_LAW_317574/f699237a8ccc0e129dbcbcb631df2013ca8600ae/
                
                    #реквизит 1023 "количество предмета расчета". Подробности в Приказе ФНС http://www.consultant.ru/document/cons_doc_LAW_214339/00d1f7e752a2ab7a388dc9ef01009024e5065b71/
                    #Значение реквизита "стоимость предмета расчета с учетом скидок и наценок" (тег 1043) должно быть равно произведению значения реквизита "цена за единицу предмета расчета с учетом скидок и наценок" (тег 1079), умноженному на значение реквизита "количество предмета расчета" (тег 1023)
                
                    #реквизит 1199 "ставка НДС" (6 - НДС не облагается). Подробности в Приказе ФНС http://www.consultant.ru/document/cons_doc_LAW_214339/b09199c4b5b68f8c82f3f4aadd06954b4c7914c9/
                
                    #реквизит 1043 "стоимость предмета расчета с учетом скидок и наценок" (тег 1043) должно быть равно произведению значения 
                    #реквизита "цена за единицу предмета расчета с учетом скидок и наценок" (тег 1079), умноженному на значение реквизита "количество предмета расчета"
                    #(тег 1023). Смотри приказ ФНС http://www.consultant.ru/document/cons_doc_LAW_214339/00d1f7e752a2ab7a388dc9ef01009024e5065b71/                
                }
            
                $tag1054_types=array('1'=>"ПРИХОД","3" => "РАСХОД", "2" => "ВОЗВРАТ ПРИХОДА", "4" => "ВОЗВРАТ РАСХОДА");
            
                if ($summ_total != $_POST['summ']*100) {
                    echo json_encode(array("status"=>"error","message"=>'Общая сумма не соответствует сумме позиций!'));
                    break;
                }
            
                $request = json_encode($request, JSON_UNESCAPED_UNICODE);
            
                if (isset($clientId)) {
                    $kktUrl='fiscalcheck.json?clientId='.$clientId;
                } else {
                    $kktUrl='fiscalcheck.json';
                }
            
                $answer=json_decode(getUrl($kktUrl, $request),true);
                if ($answer==false) {
                   //нет связи с кассой, выходим
                   echo json_encode(array("status"=>"error","message"=>'Касса не отвечает. Нужно делать новый запрос с ТЕМ ЖЕ sessionID'));
                   break;
                } else {
                   if (!isset($answer['document']['result']) or $answer['document']['result']!=0) {
                       //касса ответила, но запрос обработан с ошибкой, выходим
                       echo json_encode(array("status"=>"error","message"=>'При запросе на кассу возникла ошибка [\'document\'][\'result\']='.$answer['document']['result'].'. Нужно делать новый запрос с НОВЫМ sessionID'));
                       break;
                    }
            
                   if (isset($answer['document']['result']) and $answer['document']['result']==0) {
                       //касса ответила, запрос обработан успешно
                       $fn_nmbr='';
                       $fd_nmbr='';
                       $fpd_nmbr='';
                       foreach($answer['document']['data']['fiscprops'] as $item => $value) {
                           if($value['tag'] == "1041"){
                               $fn_nmbr=$value['value'];
                           }
                           if($value['tag'] == "1040"){
                               $fd_nmbr=$value['value'];
                           }
                           if($value['tag'] == "1077"){
                              $fpd_nmbr=$value['value'];
                           }
                       }
                       //пишем в БД информацию о созданном документе                
                       setQuery("INSERT INTO `kassa`.`onlinekassa`(`id`, `paym_id`, `datetime`, `fn_number`, `onkassa`, `onOFD`, `summ`, `client`, `fd`, `fpd`,`fisc_type`) VALUES(null,'".$sessionId."','".date("Y-m-d H:i:s")."','".$fn_nmbr."','OK','wait',".$_POST['summ'].",'".$_POST['email']."','".$fd_nmbr."','".$fpd_nmbr."','".$tag1054_types[(int)$_POST['tag1054']]."')");
                       echo json_encode(array("status"=>"ok","message"=>'Все ок. Теперь ждем подтверждения от кассы об отправке чека в ОФД'));
                  }
               }
            } else {
                echo json_encode(array("status"=>"error","message"=>'Нет поддверждения, что касса готова к приему данных для формирования документов. Проверьте состояние кассы. sessionID можно не менять'));
            }
                
        break;     
    }
}


?>