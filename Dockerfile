FROM registry.cn-hangzhou.aliyuncs.com/padkeji/base-php

COPY ./nginx/ /project/nginx/
COPY ./padphp/ /project/padphp/
COPY ./src/ /project/src/