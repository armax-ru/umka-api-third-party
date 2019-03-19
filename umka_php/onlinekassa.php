<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<meta charset="UTF-8">
<title>Онлайн-касса</title>
<script src="//ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<?php
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
$link=mysqli_connect('localhost:3306', 'root', 'root');
if (mysqli_connect_error()) {
    die('Connect Error ('.mysqli_connect_errno().') '.mysqli_connect_error());
}

//устанавливаем часовой пояс сервера
date_default_timezone_set('Asia/Yekaterinburg');
?>

<body>
<div class='main'>

    
<h1>Онлайн-касса</h1>
<div id='content' style='display: block'>
<input id='create_check' class="btn btn-send" type="button" onclick='create_check()' value="Создать чек" title="Создать чек">
<input id='kassa_status' class="btn btn-send" type="button" onclick='kassa_status()' value="Статус кассы" title="Статус кассы">
<h2 style='margin-top:15px'>Чеки</h2>
<table id='message_tab' class='childrenslist'>
    <tr><th></th><th>url</th><th>Платеж</th><th>Тип</th><th>ФП</th><th>ФН</th><th>ФД</th><th>Дата</th><th>Сумма</th><th>Клиент</th><th>На кассе</th><th>в ОФД</th></tr>
    
    
    <?php

$query = "SELECT * FROM `kassa`.`onlinekassa` WHERE 1 ORDER BY `datetime` DESC";
$res = mysqli_query($link,$query);
$messages=array();
while($row = mysqli_fetch_array($res)){
    $style='';
    if ($row['4']!='OK' or $row['5']!='OK') {$style='background:rgba(255, 165, 0, 0.4)';}
    if ($row['4']!='OK' and $row['5']!='OK') {$style='background:rgba(255, 0, 0, 0.3607843137254902)';}
    
    echo '<tr style="'.$style.'">';
    echo '<td onclick="checkinfo('.$row['8'].')">&#128269;</td>';//выводим лупу - по клику с кассы будет запрошены и выведены все данные по этому документу
    echo '<td><a href="https://cash-ntt.kontur.ru/CashReceipt/View/FN/'.$row['3'].'/FD/'.$row['8'].'/FP/'.$row['9'].'" target="_blank">Посмотреть</a></td>';//на примере ОФД Контур. Для других ОФД смотри структуру соответствующей ссылки
    echo '<td>'.$row['1'].'</td>';
    echo '<td>'.$row['10'].'</td>';
    echo '<td>'.$row['9'].'</td>';
    echo '<td>'.$row['3'].'</td>';
    echo '<td>'.$row['8'].'</td>';
    echo '<td>'.date("d.m.Y H:i:s",strtotime($row['2'])).'</td>';
    echo '<td>'.$row['6'].'</td>';
    echo '<td>'.$row['7'].'</td>';
    echo '<td>'.$row['4'].'</td>';
    echo '<td>'.$row['5'].'</td>';
    echo '</tr>';
}

?>
</table>
</div>
  
 </div>
<script charset="utf-8">
function validateEmail(email) {//валидация email
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

function kassa_status() {
    $('.main').empty()
    
    $('#create_check').remove()
    $('#kassa_status').remove()
    
    $('.main').append('<h1><a style="text-decoration: none;" href=""#><span style="color:rgb(213, 180, 91)">&#9668;</span></a><span id=\'usermsg\'>Отправляю запрос на кассу. Пожалуйста ждите!</span></h1>')
    $('.main').append('<div id=\'content\'></div>')
    //document.getElementById('content').style.display="block"
    $.ajax({
        url: "kassaactions.php",
        data: {kassa:"check_status"},
        type: "POST",
        success: function(data){
            //console.log(JSON.parse(data))
            if (JSON.parse(data)[0]=="<") {
                $('#content').append(data)
            } else {
            obj=JSON.parse(data)
            obj=JSON.parse(obj)
            //console.log(obj)
            //console.log(obj.length)
            $('#usermsg').empty();
            $('#usermsg').append('Ответ кассы:');
            if (obj==false) {
                $('#content').append('Касса не отвечает')
            } else {
            $('#content').append('<table id=\'kassa_status\'></table>')
            $('#kassa_status').append('<tr><th></th><th></th><th></th><th></th><th></th></tr>')
            for (var key in obj) {
                if (typeof(obj[key])=="object") {
                    for (var key2 in obj[key]) {
                        if (typeof(obj[key][key2])=="object") {
                            for (var key3 in obj[key][key2]) {
                                if (typeof(obj[key][key2][key3])=="object") {
                                    for (var key4 in obj[key][key2][key3]) {
                                        $('#kassa_status').append('<tr><td>'+key+'</td><td>'+key2+'</td><td>'+key3+'</td><td>'+key4+'</td><td>'+obj[key][key2][key3][key4]+'</td></tr>')
                                    }
                                } else {
                                    $('#kassa_status').append('<tr><td>'+key+'</td><td>'+key2+'</td><td>'+key3+'</td><td>'+obj[key][key2][key3]+'</td></tr>')
                                }
                            }
                        } else {
                            $('#kassa_status').append('<tr><td>'+key+'</td><td>'+key2+'</td><td>'+obj[key][key2]+'</td></tr>')
                        }
                    }
                } else {
                    $('#kassa_status').append('<tr><td>'+key+'</td><td>'+obj[key]+'</td></tr>')
                }
            }
            }
        }
        }
    });
        
}

function checkinfo(fn_numbr) {
    
    $('.main').empty()
    
    $('#create_check').remove()
    $('#kassa_status').remove()
    
    $('.main').append('<h1><a style="text-decoration: none;" href=""#><span style="color:rgb(213, 180, 91)">&#9668;</span></a><span id=\'usermsg\'>Проверяю чек! Пожалуйста ждите!</span></h1>')
    $('.main').append('<div id=\'content\'></div>')
    //document.getElementById('content').style.display="block"
   
    $.ajax({
        url: "kassaactions.php",
        data: {kassa:"checkinfo",fn:fn_numbr},
        type: "POST",
        success: function(data){
            //console.log(data)
            obj=JSON.parse(data)
            obj=JSON.parse(obj)
            //console.log(obj)
            $('#usermsg').empty();
            $('#usermsg').append('Данные по чеку с ФН='+fn_numbr+':');
            if (obj==false) {
                $('#content').append('Касса не отвечает')
            } else {
                $('#content').append('<table id=\'kassa_status\'></table>')
                $('#kassa_status').append('<tr><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th></tr>')
                for (var key in obj) {
                if (typeof(obj[key])=="object") {
                    for (var key2 in obj[key]) {
                        if (typeof(obj[key][key2])=="object") {
                            for (var key3 in obj[key][key2]) {
                                if (typeof(obj[key][key2][key3])=="object") {
                                    for (var key4 in obj[key][key2][key3]) {
                                        if (typeof(obj[key][key2][key3][key4])=="object") {
                                            for (var key5 in obj[key][key2][key3][key4]) {
                                                if (typeof(obj[key][key2][key3][key4][key5])=="object") {
                                                    for (var key6 in obj[key][key2][key3][key4][key5]) {
                                                        if (typeof(obj[key][key2][key3][key4][key5][key6])=="object") {
                                                            for (var key7 in obj[key][key2][key3][key4][key5][key6]) {
                                                                $('#kassa_status').append('<tr><td>'+key+'</td><td>'+key2+'</td><td>'+key3+'</td><td>'+key4+'</td><td>'+key5+'</td><td>'+key6+'</td><td>'+key7+'</td><td>'+obj[key][key2][key3][key4][key5][key6][key7]+'</td></tr>')
                                                            }
                                                            
                                                        } else {
                                                            $('#kassa_status').append('<tr><td>'+key+'</td><td>'+key2+'</td><td>'+key3+'</td><td>'+key4+'</td><td>'+key5+'</td><td>'+key6+'</td><td>'+obj[key][key2][key3][key4][key5][key6]+'</td></tr>')
                                                        }
                                                    }
                                                    
                                                } else {
                                                    $('#kassa_status').append('<tr><td>'+key+'</td><td>'+key2+'</td><td>'+key3+'</td><td>'+key4+'</td><td>'+key5+'</td><td>'+obj[key][key2][key3][key4][key5]+'</td></tr>')
                                                }
                                            }
                                        } else {
                                            $('#kassa_status').append('<tr><td>'+key+'</td><td>'+key2+'</td><td>'+key3+'</td><td>'+key4+'</td><td>'+obj[key][key2][key3][key4]+'</td></tr>')
                                        }
                                    }
                                } else {
                                    $('#kassa_status').append('<tr><td>'+key+'</td><td>'+key2+'</td><td>'+key3+'</td><td>'+obj[key][key2][key3]+'</td></tr>')
                                }
                            }
                        } else {
                            $('#kassa_status').append('<tr><td>'+key+'</td><td>'+key2+'</td><td>'+obj[key][key2]+'</td></tr>')
                        }
                    }
                } else {
                    $('#kassa_status').append('<tr><td>'+key+'</td><td>'+obj[key]+'</td></tr>')
                }
            }
            }
            
            }
        })
      
    };
        
function create_check() {
     $('.main').empty()
    
    $('#create_check').remove()
    $('#kassa_status').remove()
    
    $('.main').append('<h1><a style="text-decoration: none;" href=""#><span style="color:rgb(213, 180, 91)">&#9668;</span></a><span id=\'usermsg\'>Создание чека</span></h1>')
    $('.main').append('<div id=\'content\'></div>');
    
    $("#content").append('<p>Признак расчета'
       +'<select class=\'select-style\' id=\'type_selects\' onchange="rasch_type_change(this.value)">'
       +'<option value="0" selected>Надо что-то выбрать</option>'
       +'<option value="1">Приход</option>'
       +'<option value="2">Возврат прихода</option>'
       +'<option value="3">Расход</option>'
       +'<option value="4">Возврат расхода</option>'
       +'</select></p>')

    $("#content").append('<p>Email покупателя:<input type="email" style="width: 35%;margin-top:5px" placeholder="введите email" id="email"></p>')
    $("#content").append('<p>Уникальный внутренний ID (sessionID):<input type="text" style="width: 35%;margin-top:5px" placeholder="введите id" id="sessionID"></p>')
    $("#content").append('<p id=\'tag1192\'></p>')
    $("#content").append('<p>Позиции в чеке: <div id=\'check_pos\' style=\'display: inline-block;\'></div></p>');
    $("#check_pos").append('<table id=\'check_pos_table\'><th>№</th><th>Наименование</th><th>Цена (общая), руб</th><th>Кол-во</th><th>НДС</th><th>Цена за ед, руб</th></table>');
    $("#check_pos").append('<input id=\'add_pos\' style=\'background:rgba(33, 183, 58, 0.47843137254901963)\'  class="btn btn-send" type="button" onclick=\'add_pos()\' value=\'Добавить позицию в чек\'" title=\'Добавить позицию\'>')
    add_pos();
    
    $("#content").append('<p>Оплата: <div id=\'payments\' style=\'display: inline-block;\'></div></p>');
    $("#payments").append('<table id=\'payments_table\'><th>Вид</th><th>Сумма, руб</th></table>');
    $("#payments_table").append('<tr>'
    +'<td><select id=\'paym_type\'>'
        +'<option value="0"></option>'
        +'<option value="1">Наличными</option>'
        +'<option value="2">Электронными</option>'
        +'<option value="3">Предоплата</option>'
        +'<option value="4">Постоплата</option>'
        +'<option value="5">Встречное предоставление</option>'
        +'</select></td>'
    
    +'<td><input type="text" id="paym_summ" value="0.00"></td>'
    +'</tr>')
    
    
     
    $("#content").append('<input id=\'create_check_act\' class="btn btn-send" type="button" onclick=\'create_check_act()\' value=\'Сформировать документ\'" title=\'Сформировать документ\'>')
}
    

function create_check_act() {
    tag1054=document.getElementById('type_selects').value
    customer_email1008=document.getElementById('email').value
    paym_type=document.getElementById('paym_type').value
    paym_summ=parseFloat(document.getElementById('paym_summ').value)
    sessionID=document.getElementById('sessionID').value
    tag1192val='';
    
    if (tag1054==2) {
        tag1192val=document.getElementById('tag1192val').value
        if (tag1192val=="") {
            alert("Надо заполнить фискальный признак отменяемого чека для передачи в теге 1192")
            return
        }
    }
    
    
    
    if (tag1054==0) {
        alert("Не заполнен признак расчета");
        return;
    }
    
    if (sessionID=="") {
        alert("Поле с id не может быть пустым");
        return;
        
    }
    if (customer_email1008=='' || validateEmail(customer_email1008)!=true) {
        alert("email покупателя не заполнен или заполнен неверно");
        return;
    }
    if (paym_type==0) {
        alert("Не заполнен тип оплаты");
        return;
    }
    if (paym_summ==0) {
        alert("Не заполнена сумма оплаты");
        return;
    }
    
    elem=document.getElementById('check_pos_table')
    sales_data={};
    for (i=1;i<elem.rows.length;i++) {
        sales_data[i-1]={};//new Array();
        sales_data[i-1]['name']=document.getElementById('pos_'+i+'_name').value
        sales_data[i-1]['quantity']=parseFloat(document.getElementById('pos_'+i+'_quantity').value)
        sales_data[i-1]['summ']=parseFloat(document.getElementById('pos_'+i+'_summ').value)
        sales_data[i-1]['nds_type']=parseFloat(document.getElementById('pos_'+i+'_ndstype').value)
        if (sales_data[i-1]['quantity']==0 || sales_data[i-1]['summ']==0) {
            alert("Сумма и количество не могут быть 0")
            return
        }
        if (sales_data[i-1]['name']=="") {
            alert("Надо указать наименование позиции")
            return
        }
        
        if (sales_data[i-1]['nds_type']==0) {
            alert("Надо выбрать НДС")
            return
        }
        
        sales_data[i-1]['price']=parseFloat((sales_data[i-1]['summ']/sales_data[i-1]['quantity']).toFixed(2));
    }
    
    
    
    $.ajax({
        url: "kassaactions.php",
        data: {kassa:"create_document",id_paym:sessionID,moneyType:paym_type,summ:paym_summ,inn:"7725225244",tag1054:tag1054,email:customer_email1008,sales:JSON.stringify(sales_data),tag1192:tag1192val},
        type: "POST",
        success: function(data){
            obj=JSON.parse(data)
            //console.log(data)
            alert(obj['message'])
            if (obj['status']=="ok") {
                location.reload()
            }
        }
    });  
}

function add_pos() {
    elem=document.getElementById('check_pos_table')
    nmrrows=elem.rows.length
    
    $("#check_pos_table").append('<tr>'
    +'<td>'+nmrrows+'</td>'
    +'<td><input type="text" id="pos_'+nmrrows+'_name" placeholder="Наименование позиции '+nmrrows+'"></td>'
    +'<td><input type="text" id="pos_'+nmrrows+'_summ" value="0.00" onchange=calcperunit('+nmrrows+')></td>'
    +'<td><input type="text" id="pos_'+nmrrows+'_quantity" value="0.00" onchange=calcperunit('+nmrrows+')></td>'
    +'<td><select id="pos_'+nmrrows+'_ndstype"><option value="0"></option>'
          +'<option value="1">НДС 20%</option>'
          +'<option value="2">НДС 10%</option>'
          +'<option value="3">НДС 20/120</option>'
          +'<option value="4">НДС 10/110</option>'
          +'<option value="5">НДС 0%</option>'
          +'<option value="6">Без НДС</option>'
          +'</select></td>'
    +'<td>1.00</td>'
    +'</tr>');
    
}

function calcperunit(nmrrows) {
    totalprice=parseFloat(document.getElementById('pos_'+nmrrows+'_summ').value)
    quat=parseFloat(document.getElementById('pos_'+nmrrows+'_quantity').value)
    document.getElementById('check_pos_table').rows[nmrrows].cells[5].innerHTML=(totalprice/quat).toFixed(2)
}

function rasch_type_change(priznak){
    //если выбран Возврат прихода просим пользователя указать фискальный признак отменяемого документа для включения в тег 1192
    if (priznak==2) {
        $('#tag1192').append('Фискальный признак отменяемого документа (для тега 1192)<input type="text" style="width: 35%;margin-top:5px" placeholder="введите ФП" id="tag1192val">')
    } else {
        $('#tag1192').empty();
    }
}

</script>
</body>