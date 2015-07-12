# Laravel5MigrationFromDatabase
Importa uma database para suas migrations, criando as migrations por tabela.

Usage
-- Copie a classe Dbmigrate.php para dentro de app.
-- Edite a classe apontando sua database a ser migrada e possíveis tabelas ignoradas.
-- Usando o prompt (shell), na pasta raiz do projeto, entre com o comando 'php artisan tinker'.
-- Dentro do tinker, faça:
    >>> use App\Dbmigrate
    >>> Dbmigrate::generate()
-- Pront! Acesse a pasta database/migrations, e os arquivos estarão disponíveis! 
