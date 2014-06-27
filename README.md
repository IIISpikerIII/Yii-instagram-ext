Yii-instagram-ext
=================

Yii instagram-ext

В этом расширении за основу были взяты:
расширение http://www.yiiframework.com/extension/yiinstagram/, которое использует библиотеку PHP для работы с API Instagram https://github.com/macuenca/Instagram-PHP-API и немного переработаны.

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
   'class' => 'ext.yiinstagram.InstagramEngine',
   'config' => array (
                 'client_id' => 'YOUR_CLIENT_ID',
                 'client_secret'   => 'YOUR_CLIENT_SECRET',
                 'grant_type' => 'authorization_code',
                 'redirect_uri' => 'YOUR_CALLBACK_URL',
                 )
)
```
