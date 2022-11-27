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

下面以1.4版本为例进行部署（已发行版本列表[releases](https://github.com/winterant/OnlineJudge/releases)）。

获取配置文件并解压：
```bash
wget https://github.com/winterant/OnlineJudge/releases/download/1.4/lduoj-v1.4.zip
unzip lduoj-v1.4.zip
```

启动服务：
```bash
cd lduoj-v1.4
docker-compose up -d
```

- 访问首页`http://ip:8080`；可在宿主机[配置域名](/deploy/network.md)；
- 默认管理员用户：`admin`，默认密码`adminadmin`，务必更改默认密码；

## 🚗 升级

- 版本内更新(docker tag不变，无须修改任何配置)，一般会更新一些影响较小的bug修复。
  ```bash
  docker-compose pull web judge
  docker-compose up -d
  ```
- 跨版本升级（例如1.3升级到1.4）  
  1. 参照【部署】获取新版本并解压；
    ```bash
    wget https://github.com/winterant/OnlineJudge/releases/download/1.4/lduoj-v1.4.zip
    unzip lduoj-v1.4.zip
    ```
  2. 将旧版本中的`./data/`文件夹移动到新版本文件夹中；
    ```bash
    mv lduoj-v1.3/data lduoj-v1.4/
    ```
  3. 修改必要的配置；
    ```bash
    vim lduoj-v1.4/lduoj.conf
    ```
  4. 在新版本文件夹中启动服务即可；
    ```bash
    cd lduoj-v1.4
    docker-compose up -d
    ```

## 💿 备份/迁移

### 备份
1. 将`docker-compose.yml`所在文件夹打包备份；
    ```bash
    tar -cf - ./lduoj | pigz -p $(nproc) > lduoj_bak.tar.gz
    ```

### 恢复
1. 解压备份包
    ```bash
    tar -zxvf lduoj_bak.tar.gz
    ```
2. 一键部署
    ```bash
    cd lduoj_bak
    docker-compose up -d
    ```
