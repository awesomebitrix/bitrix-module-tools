#### [Главная страница](../README.md)

## Менеджер доступов

Сервис предоставляет возможность гибкой организации доступов к функционалу. Позволяет избавится от дублей проверок доступа пользователя. 
Объектом доступа является ресурс над которым предусмотрены те или иные действия. Менеджер доступов предоставляет объект доступа к ресурсу для текущего пользователя
у которого можно запросить разрешение на выполнение действий с ресурсом. Тип досутупа определяет роли пользователя относительно ресурса с которым происходит работа.

Пример: Есть некая статья на сайте. Нужно организовать к ней доступ на добавление, радактирование, удаление и просмотр.

* Промотр доступен для всех пользователей
* Удаление доступно только для пользователей группы админстраторы
* Добавлять статьи могут польователи групп редакторы и администраторы
* Доступ для редактирования статьи есть только у автора

Обработка данного примера послужит описанием функционала

#### Определение типа доступа
Тип доступа и его обработка необходимо определить в классе наследуемом от базового класса ```\WS\Tools\Access\Access```, для которого достаточно организовать всего один метод ```getRoles```.
Данный метод должен вернуть список ролей пользователя относительно ресурса. Логика вычисления ролей пользователя должна быть определена в этом методе.

Пример:
```php
<?php

class ArticleAccess extends \WS\Tools\Access\Access {
    /**
     * @return array
     */
    public function getRoles() {
        $groups = $this->getUserGroups();
        $arArticle = $this->getResource();
        
        $list = array('anybody');
        
        if (in_array('admin', $groups)) {
            $list[] = 'admin';
        }
        if (in_array('editor', $groups)) {
            $list[] = 'editor';
        }
        if ($arArticle['CREATED_BY'] == $this->getUser()->GetID()) {
            $list[] = 'author';
        }
        
        return $list;
    }
}
```
В примере определен алгоритм вычисления списка ролей польователя при работе с ресурсом. Здесь ресурсом является статья

#### Инициализация

В конфигурации настроек сервисов определить менеджер доступов с настройками

```php
<?php
$config = array(
    "services" => array(
        // ............
        'accessManager' => array(
            ArticleAccess::className() => array( // класс определяющий логику доступа
            // действие | перечень ролей для выполнения действия
                'view' => array(
                    'anybody'
                ),
                'add' => array(
                    'editor', 'admin'
                ),
                'edit' => array(
                    'author'
                ),
                'delete' => array(
                    'admin'
                )
            )
        )
        // ............
    )
);

```

#### Использование

Для использования функционала необходимо запросить объект доступа для конкретной статьи и обращаться к нему для
уточнения доступа к конкретным действиям над ресурсом

```php
<?php

// непосркдственно данные статьи
$arArticle = CIBlockElement::GetByID(112)->Fetch();

/** @var \WS\Tools\Access\AccessManager $accessManager */
$accessManager = $wsTools->getService('accessManager');

$access = $accessManager->getAccess(ArticleAccess::className(), $arArticle);

$access->can('view') && print("can view");
$access->can('edit') && print("can edit");
$access->can('delete') && print("can delete");
$access->can('add') && print("can add");
```

#### [Главная страница](../README.md)