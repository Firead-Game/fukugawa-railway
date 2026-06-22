FROM php:8.2-apache

# PDO MySQL 拡張機能をインストール
RUN docker-php-ext-install pdo pdo_mysql

# 💡 RenderのRoot Directoryを「app」にしたので、ここは「public」だけでOKになります
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# ApacheのRewriteモジュールを有効化
RUN a2enmod rewrite

# ソースコードをコンテナ内にコピー
COPY . /var/www/html/

# 適切な権限を設定
RUN chown -R www-data:www-data /var/www/html