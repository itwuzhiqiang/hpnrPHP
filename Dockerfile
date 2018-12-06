FROM registry.cn-hangzhou.aliyuncs.com/com-hpnr/com-hpnr-registry

COPY ./nginx/ /project/nginx/
COPY ./padphp/ /project/padphp/
COPY ./src/ /project/src/
