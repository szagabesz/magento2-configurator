mysql -uroot -e "CREATE DATABASE magentodb;"
mysql -uroot -e "GRANT ALL PRIVILEGES ON magnetodb.* TO 'magento'@'localhost' IDENTIFIED BY 'magento';FLUSH PRIVILEGES;"
gunzip < features/data/magento-demo.sql.gz | mysql magentodb -uroot