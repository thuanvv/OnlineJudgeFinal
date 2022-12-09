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

## 🚗 升级

- **版本内更新**；版本号不变，无须修改任何配置，一般会修复一些影响较小的bug修复或开发一些小功能。
  ```bash
  docker-compose pull web judge
  docker-compose up -d
  ```
- **跨版本升级**；例如v1.4升级到v1.5。原理是替换现有的`docker-compose.yml`,`lduoj.conf`为[最新部署脚本](https://github.com/winterant/OnlineJudge/tree/deploy)。  

  升级之前，请先停止服务：
  ```bash
  docker-compose down
  ```
  
  您有两种方式来更新您的本地部署脚本：
  - 第一种，您完全可以查看最新部署脚本发生了哪些改动([commits](https://github.com/winterant/OnlineJudge/commits/deploy))，自行修改本地脚本即可。
  - 第二种，如果您不清楚最新部署脚本做了哪些改动，那么可以参照上文部署方式重新下载[最新部署脚本](https://github.com/winterant/OnlineJudge/tree/deploy)，以替换掉现有的部署脚本，并自行修改配置`lduoj.conf`。

  然后，重新启动服务即可：
  ```bash
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
