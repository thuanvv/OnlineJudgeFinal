# 安装维护

## 🍷 准备工作

1. 安装`docker`；[参考文档](https://yeasy.gitbook.io/docker_practice/install/ubuntu#shi-yong-jiao-ben-zi-dong-an-zhuang)
  ```bash
  sudo curl -fsSL https://get.docker.com | bash -s docker --mirror Aliyun
  # 启动docker
  sudo systemctl enable docker
  sudo systemctl start docker
  ```
2. 安装`docker-compose`；[参考文档](https://yeasy.gitbook.io/docker_practice/compose/install)
  ```bash
  sudo curl -L "https://github.com/docker/compose/releases/download/v2.2.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
  sudo chmod +x /usr/local/bin/docker-compose
  sudo ln -s /usr/local/bin/docker-compose /usr/bin/docker-compose
  docker-compose version
  ```

## 🔨 部署

获取最新部署脚本（也可以自行前往网页下载）：
```bash
git clone -b deploy https://github.com/winterant/OnlineJudge.git
```

启动服务：
```bash
cd OnlineJudge
docker-compose up -d
```

- 访问首页`http://ip:8080`；可在宿主机[配置域名](/deploy/network.md)；
- 默认管理员用户：`admin`，默认密码`adminadmin`，务必更改默认密码；

## 🚗 升级(Web端)

- **版本内更新**；版本号不变，无须修改任何配置，一般涉及一些影响较小的bug修复。
  ```bash
  docker-compose pull web
  docker-compose up -d
  ```
- **跨版本升级**；版本号改变，一般涉及新功能的开发或重大bug的修复。跨版本升级的原理是将`docker-compose.yml`,`lduoj.conf`更新为[最新部署脚本](https://github.com/winterant/OnlineJudge/tree/deploy)。
随着版本的迭代升级，[最新部署脚本](https://github.com/winterant/OnlineJudge/tree/deploy)中某些配置项可能会发生变动，不过这种情况很少出现。**大多数情况下，仅仅只需要手动修改一下`docker-compose.yml`中的`winterant/lduoj`的版本号**。如果您的版本落后于官方版本很多或不清楚部署脚本需要做哪些改动，那么建议您人工对比一下本地部署脚本和[最新部署脚本](https://github.com/winterant/OnlineJudge/tree/deploy)的差异，自行修改。

  1. 升级之前，最好先停止服务（理论上不停止服务也没事）：
  ```bash
  docker-compose down
  ```
  2. 修改`docker-compose.yml`中`winterant/lduoj`版本号为最新；同时，按上述说法修改其它变动(如有)；
  3. 拉取最新镜像并重新启动服务即可：
  ```bash
  docker-compose pull web  # 拉取最新镜像
  docker-compose up -d
  ```

## 💿 备份/迁移

### 备份
1. 将`docker-compose.yml`所在文件夹打包备份；
    ```bash
    tar -cf - ./OnlineJudge | pigz -p $(nproc) > OnlineJudge_bak.tar.gz
    ```

### 恢复
1. 解压备份包
    ```bash
    tar -zxvf OnlineJudge_bak.tar.gz
    ```
2. 一键部署
    ```bash
    cd OnlineJudge_bak
    docker-compose up -d
    ```
