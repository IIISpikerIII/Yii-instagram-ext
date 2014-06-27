Yii-instagram-ext
=================

Yii instagram-ext

В этом расширении за основу были взяты:
расширение http://www.yiiframework.com/extension/yiinstagram/, которое использует библиотеку PHP для работы с API Instagram https://github.com/macuenca/Instagram-PHP-API и немного переработаны.

Данное расширене позволяет работать с API инстаграма. Получать информацию о сделаных фотографиях, лайках, комментариях, пользователях и др.

##Сделано

- добавлена возможность получения информации не только авторизованным пользователям 
- собраны все предоставляемые API функции на данный момент времени
- добавлена подписка на обновления Real-time
- расширены имеющиеся функции дополнительными параметрами запроса к API

##Использование

- регистрируем свое приложение http://instagr.am/developer/clients/manage/
- копируем все в папку extension
- добавляем в /protected/config/main.php в секцию components 
 
```php
'instagram' => array(
   'class' => 'ext.yii-instagram-ext.InstagramEngine',
   'config' => array (
                 'client_id' => 'YOUR_CLIENT_ID',
                 'client_secret'   => 'YOUR_CLIENT_SECRET',
                 'grant_type' => 'authorization_code',
                 'redirect_uri' => 'YOUR_CALLBACK_URL',
                 )
)
```

Далее в нужном экшене 
```php
$instagram = Yii::app()->instagram->getInstagramApp();  
//если планируется авторизация пользователя
$instagram->openAuthorizationUrl();
```

###Авторизация
При попытке авторизации на url callback придет GET параметр code

```php
if (isset($_GET['code'])) {
    $session = Yii::app()->getSession();
    $accessToken = $instagram->getAccessToken();
    $instagram->setAccessToken($accessToken); 
```
###Пример
```php
$instagram = Yii::app()->instagram->getInstagramApp(); 
$user=$instagram->getUser('394809993',false);
print_r($user);
```
###Пользователи

* getCurrentUser()
* getUser($id,$auth) 
* getUserFeed($maxId = null, $minId = null, $count = null)
* getUserRecent($auth=false,$id, $count = '', $minTimestamp = '', $maxTimestamp = '', $minId = '', $maxId = '')
* searchUser($name,$count,$auth=false)
* getUserFollows($id,$auth=false)
* getUserFollowedBy($id,$auth=false)
* getUserRequestedBy() 
* getUserRelationship($id)
* modifyUserRelationship($id, $action)

###Медиа

* getMedia($id, $auth=false)
* getMediaShort($mediaShort, $auth=false)
* mediaSearch($lat, $lng, $maxTimestamp = '', $minTimestamp = '', $distance = '')
* getPopularMedia($auth=false)

###Комментарии

* getMediaComments($id, $auth=false)
* postMediaComment($id, $text)
* deleteComment($mediaId, $commentId)

###Лайки

* getLikes($mediaId, $auth=false)
* postLike($mediaId)
* removeLike($mediaId)

###Теги

* getTags($tagName, $auth=false)
* getRecentTags($tagName, $auth=false, $maxId = '', $minId = '')
* searchTags($tagName,$auth=false)

###Локация

* getLocation($id,$auth=false)
* getLocationRecentMedia($id, $auth=false,$maxId = '', $minId = '', $maxTimestamp = '', $minTimestamp = '')
* searchLocation($lat, $lng, $auth, $foursquareId = '', $distance = '') 
* geographiesRecent($id, $auth=false, $count='', $min_id = '')

## Подписка на Real Time

На данный момент написана подписка на user 

RegSubscription($url_callback)

В ответ на данный запрос на callback придет GET запрос на котолрый необходимо ответить

```php
//  ответ для регистрации подписки
        if(!isset($_GET['hub_challenge']))
            print_r($instagram->RegSubscription());
        else{
            print $_GET["hub_challenge"]; exit(1);
        }
```
После чего по адресу указанному в регистрации можно принимать POST запросы. Для получени и проверки корректности пришедших данных можно воспользоваться кодом

```php
     if(isset($_POST)&& isset($_SERVER['HTTP_X_HUB_SIGNATURE'])){

            $xhub=$_SERVER['HTTP_X_HUB_SIGNATURE'];
            $posted_data = file_get_contents("php://input");

           //если проверка прошла, работаем
           if($media=$instagram->GetNewSubscription($xhub,$posted_data)){
```
Далее объект $media будет содержать информацию о пришедшем медиа фрагменте
