mysql -uroot -e "CREATE DATABASE magentodb;"
mysql -uroot -e "GRANT ALL PRIVILEGES ON magnetodb.* TO 'magento'@'localhost' IDENTIFIED BY 'magento';"
gunzip magento-demo.gz | mysql magentodb -uroot