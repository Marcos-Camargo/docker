### Configuração para corrigir o erro de group by
Na tela de login dá o seguinte erro: 

    which is not functionally dependent on columns in GROUP BY clause; this is incompatible with sql_mode=only_full_group_by

Para corrigir execute o seguinte comando.

    use fase1;
    
## Ativação do sistema.

Copie o arquivo .env.example para .env e configure de acordo com os dados desejado ao banco, com o code-igniter estão no arquivo config/database.php. Apos feito isto basta rodar o script make usando o comando `make run`

O codigo do codeigniter vai na pasta public dentro do diretorio raiz

## Dependencias
* Docker-cli
* Docker-compose
* ultilizatio make

## Programas uteis para o desenvolvimento.
* DBeaver
* WinSCP