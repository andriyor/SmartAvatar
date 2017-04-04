<?php
//_____НАСТРОЙКИ_____\\
/*
        Чтобы получить токен -- перейдите по ссылке, разрешните доступ и из адресной строки скопируйте занчение access_token
http://oauth.vk.com/authorize?response_type=token&client_id=3213852&scope=photos,friends,offline,wall,messages
*/
$token = ''; //Токен
$skype = 'ololo'; //Логин в скайпе
$msg   = "Ты пидор"; //Сообщение на аватарке



addlog('Start!');
//Ключ проверяющий первый запуск или нет
$firstLaunch = true;
while (true) {
        //Получаем нужные данные с ВК шечки
        //Получаем количество новых сообщений
        addlog('Get inform');
        $getMsg   = api('messages.get','access_token='.$token.'&filters=1');
        $msgCount = $getMsg['response'][0];
        //Получаем количество онлайн юзеров
        $getOnline   = api('friends.getOnline', 'access_token='.$token);
        $onlineCount = count($getOnline['response']);
        //Получаем количество заявок
        $getRequests  = api('friends.getRequests', 'access_token='.$token.'&count=1000');
        $requestCount = count($getRequests['response']);
        //Получаем данные из скайпа
        //Получаем статус онлайн\оффлайн
        $skype = file_get_contents('http://mystatus.skype.com/'.$skype.'.txt');
        //А тут уже работам с созданием пикчи
        //Клеим фон и интерфейс
        $path  = dirname(__FILE__); 
        $top   = imagecreatefrompng($path.'/top.png');
        $img   = $path.'/bg.png';
        $size  = getimagesize($img);
        $image = imagecreatefrompng($img);
        $color = imagecolorallocate($image, 255, 255, 255); 
        imagecopyresampled($image, $top, 0, 0, 0, 0, $size[0], $size[1], $size[0], $size[1]);
        //Рисуем текст
        //Отрисовка всякой поебени
        $textForImg = array(
                'Сообщений' => $msgCount, 
                'Онлайн'    => $onlineCount, 
                'Заявок'    => $requestCount, 
                'Skype'     => $skype
                );
        $i = 130;
        foreach ($textForImg as $key => $value) {
                imagettftext($image, 12, 0, 30, $i, $color, $path.'/fonts/seguisb.ttf', $key.': ');
                imagettftext($image, 12, 0, 170, $i, $color, $path.'/fonts/seguisb.ttf', $value);
                $i += 20;
        }
        //Отрисовка блока сообщения
        imagettftext($image, 12, 0, 80, 210, $color, $path.'/fonts/seguisb.ttf', "Сообщение");
        imagettftext($image, 9, 0, 30, 230, $color, $path.'/fonts/seguisb.ttf', $msg);
        //Отрисовка времени
        imagettftext($image, 30, 0, 65, 322, $color, $path.'/fonts/segoeuib.ttf', date('H:i'));
        //Отрисовка даты
        imagettftext($image, 10, 0, 80, 335, $color, $path.'/fonts/segoeuib.ttf', date('d.m.Y'));
        //Сейвим результ
        imagepng($image, $path.'/result.png'); 
        //Асвабаждаем рысурсы
        imagedestroy($image); 
        //Проверяем, елси это первый запуск, то нахуй, если нет, дропаем предыдущее фото
        if (!$firstLaunch) {
                addlog('Delete old avatar!');
                $getAllPhoto  = api('photos.getProfile', 'access_token='.$token);
                $infLastPhoto = end($getAllPhoto['response']);
                api('photos.delete', 'pid='.$infLastPhoto['pid'].'&access_token='.$token);
        }
        //А тут мы будем грузить фоточку
        //Получаем url для аплоада
        addlog('Upload avatar!');
        $getUploadServer = api('photos.getProfileUploadServer', 'access_token='.$token);
        $uploadUrl       = $getUploadServer['response']['upload_url'];
        //Загружаем
        $uploadPhoto = curl($uploadUrl,  array('photo' => '@'.$path.'/result.png'));
        $uploadJson  = json_decode($uploadPhoto, true);
        //Сохраняем
        $savePhoto = api('photos.saveProfilePhoto', 'access_token='.$token.'&server='.$uploadJson['server'].'&photo='.$uploadJson['photo'].'&hash='.$uploadJson['hash']);
        //Получаем запись о обновление фото
        $getWall   = api('wall.get','count=1&access_token='.$token);
        $wllMsgId  = $getWall['response'][1]['id'];
        //Удаляем запись о обновление
        api('wall.delete', 'post_id='.$wllMsgId.'&access_token='.$token);
        //Задаём ключ, который говорит что мы уже грузили фото
        $firstLaunch  = false;
        addlog('Done!');
        sleep(10); // тут настраиваем время обновления аватара советую ставить раз в 5 минут
}
//Апишечка
function api($method, $parametrs) {
	$getApi = curl('https://api.vk.com/method/'.$method.'?'.$parametrs);
	return json_decode($getApi, true);
}
//Лог
function addlog($text){
        echo date('H:i:s: ').$text.PHP_EOL;
}               
//Курлик
function curl($url, $post = false) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.94 Safari/537.4 AlexaToolbar/alxg-3.1');
	if ($post) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	$response = curl_exec ($ch);
	curl_close($ch);
	return $response;
}
?>