<?php

namespace App;

use DiDom\Document;

class Runner
{
    private $_config = [];
    private $_request;

    private $_data = [];

    public function __construct($config)
    {
        $this->_config = $config;
        $this->_request = new Request($config['proxy']);
    }

    public function start()
    {
        try {
            /* Авторизуемся */
            $this->auth();

            /* Получаем данные из профиля*/
            $this->getProfileData();

            /* Получаем данные из истории*/
            $this->getHistoryData();

            /* Выводим */
            echo '<pre>';
            var_dump($this->_data);
            echo '</pre>';

        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }

    public function auth()
    {
        $login = $this->_config['auth']['login'];
        $password = $this->_config['auth']['password'];

        $this->_request->get($this->_config['url']['login']);

        $url = $this->_config['url']['loginAjax'];
        $url = str_replace('{login}', $login, $url);
        $url = str_replace('{password}', $password, $url);

        /* Заголовки для корректной работы запросов */
        $headers = [
            "Referer:  https://www.myarena.ru/login.html",
            "Sec-Fetch-Dest:  empty",
            "Sec-Fetch-Mode:  cors",
            "Sec-Fetch-Site:  same-origin",
            "User-Agent:  Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.42 YaBrowser/20.11.0.419 (beta) Yowser/2.5 Safari/537.36",
            "X-Requested-With:  XMLHttpRequest"
        ];

        /* Для авторизации достаточно выполнить GET запрос на указанный URL и получить 200 в ответе */
        $data = $this->_request->get($url, $headers);
        if ($data['status'] != 200) throw new \Exception('Login error');
    }

    public function getProfileData()
    {
        /* Получаем html*/
        $url = $this->_config['url']['profile'];

        $headers = [
            "Referer:  https://www.myarena.ru/login.html",
            "Sec-Fetch-Dest:  document",
            "Sec-Fetch-Mode:  navigate",
            "Sec-Fetch-Site:  same-origin",
            "Sec-Fetch-User:  ?1",
            "User-Agent:  Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.42 YaBrowser/20.11.0.419 (beta) Yowser/2.5 Safari/537.36"
        ];

        $resource = $this->_request->get($url, $headers)['data'];

        /* Заюзал DiDOM, хорошая библиотека */
        $document = new Document($resource);

        /* Если нет такого узла, значит мы не туда попали */
        if (!$document->has('.profile-data')) {
            throw new \Exception("Ошибка парсинга страницы " . $url);
        }

        /* Собираем данные из левого блока, нужные данные в теге b */
        /* Можно еще активность выгрузить, но там ничего полезного */
        $profileData = $document->first('.profile-data')->first('.pf-right');

        /* Собираем данные из правого блока */
        $regDate = $profileData->find('.pf-right-dop')[0]->first('b')->text();

        $dataRight = $profileData->find('.pf-right-dop1');
        $balance = $dataRight[0]->first('b')->text();
        $number = $dataRight[1]->first('b')->text();

        /* Сохраняем данные до вывода */
        $this->_data['profile'] = [
            'reg_date' => $regDate,
            'balance' => $balance,
            'number' => $number
        ];
    }

    public function getHistoryData()
    {
        $headers = [
            "Referer:  https://www.myarena.ru/login.html",
            "Sec-Fetch-Dest:  document",
            "Sec-Fetch-Mode:  navigate",
            "Sec-Fetch-Site:  same-origin",
            "Sec-Fetch-User:  ?1",
            "User-Agent:  Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.42 YaBrowser/20.11.0.419 (beta) Yowser/2.5 Safari/537.36"
        ];

        $url = $this->_config['url']['history'];
        $resource = $this->_request->get($url, $headers)['data'];

        $document = new Document($resource);

        if (!$document->has('.bodytbl')) {
            throw new \Exception("Ошибка парсинга страницы " . $url);
        }

        /* Получаем строки таблицы*/
        $rows = $document->first('.bodytbl')->find('tr');

        /* Убираем заголовок таблицы */
        array_shift($rows);

        foreach ($rows as $row) {
            $columns = $row->find('td');

            $date = $columns[0]->first('nobr')->text();
            $ip = $columns[1]->text();

            /* Индекс 4 выявлен опытным путем */
            $info = $columns[2]->children()[4]->text();

            $this->_data['history'][] = [
                'date' => $date,
                'ip' => $ip,
                'info' => $info
            ];
        }
    }
}
