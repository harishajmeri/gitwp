Workflow template for wordpress localdev:

git clone --no-hardlinks /var/www/load/wptemplate projectname

[
create a mysql db with user and pass
]

edit wp-config.php 
[
add mysql credentials
add and commit 
]

Setup vhosts (guide):

sudo cp /etc/apache2/sites-available/default /etc/apache2/sites-available/example.local

[ EDIT 
/etc/apache2/sites-available/example.local
ServerAdmin webmaster@example.local
ServerName example.local
ServerAlias www.example.local
DocumentRoot /var/www/projectname
]

sudo a2ensite example.local

[EDIT  
/etc/hosts
#Virtual Hosts 
127.0.0.1  example.local
]

sudo service apache2 restart

Visit: http://example.local and fillup the setup page