# Tri par défaut personnalisé

La méthode `setDefaultSort` permet  de définir le tri par défaut :

```php
<?php
//src/Controller/MyCrudController
namespace App\Controller;

use Ecommit\CrudBundle\Crud\Crud;
//...

class MyCrudController extends AbstractCrudController
{
    protected function getCrudOptions(): array
    {
        //...
        
        $crudConfig = $this->createCrudConfig('my_crud'); //Passé en argument: Nom du CRUD
        $crudConfig->addColumn(['id' => 'id', 'alias' => 'c1.id', 'label' => 'Id'])
            //...
            ->setRoute('my_crud_ajax')
            ->setDefaultSort('id', Crud::ASC)
            //...

        return $crudConfig->getOptions();
    }

    //...
}
```

Sur deux colonnes (ici va trier sur c1.id puis c1.name) :

```php
<?php
//src/Controller/MyCrudController
namespace App\Controller;

use Ecommit\CrudBundle\Crud\Crud;
//...

class MyCrudController extends AbstractCrudController
{
    protected function getCrudOptions(): array
    {
        //...
        
        $crudConfig = $this->createCrudConfig('my_crud'); //Passé en argument: Nom du CRUD
        $crudConfig->addColumn(['id' => 'id', 'alias' => 'c1.id', 'label' => 'Id',  'alias_sort' => ['c1.id', 'c1.name']])
            //...
            ->setRoute('my_crud_ajax')
            ->setDefaultSort('id', Crud::ASC)
            //...

        return $crudConfig->getOptions();
    }

    //...
}
```

> **_SÉCURITÉ:_** Les valeurs de l'option `alias_sort` sont injectées telles quelles (sans échappement) dans la
> clause `ORDER BY` de la requête générée. Elles doivent donc être des valeurs écrites en dur dans le code et ne
> jamais être construites à partir de données provenant de l'utilisateur.

Il est aussi possible de définir un tri par défaut personnalisé grâce à la méthode `setDefaultPersonalizedSort` :

```php
<?php
//src/Controller/MyCrudController
namespace App\Controller;

use Ecommit\CrudBundle\Crud\Crud;
//...

class MyCrudController extends AbstractCrudController
{
    protected function getCrudOptions(): array
    {
        //...
        
        $crudConfig = $this->createCrudConfig('my_crud'); //Passé en argument: Nom du CRUD
        $crudConfig->addColumn(['id' => 'id', 'alias' => 'c1.id', 'label' => 'Id'])
            //...
            ->setRoute('my_crud_ajax')
            ->setDefaultPersonalizedSort([
                'c1.purchaseDate' => Crud::DESC,
                'c1.id' => Crud::ASC,
            ])
            //...

        return $crudConfig->getOptions();
    }

    //...
}
```

> **_SÉCURITÉ:_** Contrairement au tri sur une colonne (validé par rapport aux colonnes déclarées), les alias passés
> à `setDefaultPersonalizedSort` sont injectés tels quels (sans échappement ni validation) dans la clause `ORDER BY`
> de la requête générée. Ils doivent donc être des valeurs écrites en dur dans le code et ne jamais être construits
> à partir de données provenant de l'utilisateur, sous peine d'injection SQL.
