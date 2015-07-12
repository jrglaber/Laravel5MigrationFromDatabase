# Laravel5MigrationFromDatabase
Importa uma database para suas migrations, criando as migrations por tabela.

<b style="font-size: 20px;">Usage</b><br>
<ul>
<li>Copie a classe Dbmigrate.php para dentro de app.</li>
<li>Edite a classe apontando sua database a ser migrada e possíveis tabelas ignoradas.</li>
<li>Usando o prompt (shell), na pasta raiz do projeto, entre com o comando 'php artisan tinker'.</li>
<li>Dentro do tinker, faça:<br>
    >>> use App\Dbmigrate<br>
    >>> Dbmigrate::generate()</li>
<li>Pronto! Acesse a pasta database/migrations, e os arquivos estarão disponíveis! </li>
</ul>
