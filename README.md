# Кой Говори? /Дарик Радио/ към Подкяст
Това репо съдържа информация за програмният код и преобразуването от предването Кой Говори? на Дарик Радио до Подкяст

Инсталация: 
- Свалете файловете от репото
- В Google Console (https://console.developers.google.com) създайте нов проект и изберете Blogger API v3 от менюто Library
- От менюто Credentials изберете "Create creadential" и след това OAuth Client ID
- Свалете JSON файла, който ще се генерира след като създадете OAuth Client ID
- Поставете файла JSON извън репото и обновете линковете в двата файла на:
```
kojGovoriApi.php:9 $client->setAuthConfig(//ДОБАВИ ФАЙЛА ТУК);
```
```
oauth2callback.php:9 $client->setAuthConfig(//ДОБАВИ ФАЙЛА ТУК);
```
- Инталирайте google api client чрез composer чрез този код:
```
composer require google/apiclient:^2.0
```
- След това добавете пътят към autoload.php в началото на двете страници:
```
require_once '/path/to/your-project/vendor/autoload.php';
```
- Много е важно да направим Cron Job, за да може предаванията да се добавят автоматично. Това е моят cron job, който зарежда файла всяка вечер в 12:30 през нощта, вашият може да е по-различен:
```
30 0 * * * curl http://localhost/darik/kojGovori/kojGovoriApi.php
```

Как работи кода:
- Благодарение на refreshToken-а може да получите офлайн достъп до данните си в google blogger, за да не трябва да разрешавате всеки път достъп до тях, защото ще направим cronjob в последствие. Благодарение на това автоматично ще извличаме новите предавания всяка вечер в 12 часа през нощта.
- На ред 30 на файла kojGovoriApi.php трябва да добавите идентификационният номер на блога си. Този ред проверява последният добавен пост в блога и гладодарение на това може да видите кои постове не са добавени след това.
```
$posts = $client->posts->listPosts("ADD BLOG ID", array("maxResults" => 1))->getItems(); //TODO: ADD BLOG ID
```
- Благодарение на $curl = curl_init(); командата прави заявка към: 'http://darikradio.bg/audio.list.ajax.php?showId=14&page=0' showId=14 е предаването Кой Говори? Вие може да използвате и друго предаване.
- Взимаме всички връзки/линкове към последните 20 епизода (общият им брой на страница са 20).
```
$re = '/onclick=\"dl\.url\(\'(\S+)\'\)">/';
```
- Ред 72 правим заявка към всеки един епизод:
```
CURLOPT_URL => $url
```
- Посредством regex взимаме заглавието, снимката, съдържанието, линк към mp3 файла и водещият на епизода:

```
  $findTitle = '/<title>(.+)<\/title>/'; // Заглавие
  $findAuthor = '/<a class="author".*>(.*)<\/a>/'; // Автор
  $findImage = '/<img src="(\/media\/.+)" data-src=/'; //Снимка
  $findContent = '/<div class="rte">(.*<\/p>)<\/div>/s'; // Съдържание
  $findmp3 = '/<audio src="(.+)"><\/audio>/'; // MP3 файл
```
- Проверяваме дали вече този епизод не е добавен в блога:
```
if (!empty($posts) && $srcTitle == $posts[0]->title) {
	break;
}
```
- Добавяме предаванията в списък, за да може после да ги добавим чрез цикъл, който да започне от пред-назад, за да се добавят предаванията в последователен ред:
```
$newPosts[] = $blogPost;
```
- Накрая отпечатваме, че всички предвания са добавени успешно:
```
echo "<h1>Posts updated...</h1>";
```
