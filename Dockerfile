FROM com-hpnr-registry/hpnrPHP

COPY ./nginx/ /project/nginx/
COPY ./padphp/ /project/padphp/
COPY ./src/ /project/src/