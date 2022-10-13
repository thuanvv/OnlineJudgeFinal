# 开发手册

>说明：系统在开发初期并没有考虑过多的开发原则，没有实现前后端分离，没有遵循RESTful标准等。
即日起，开发将尽最大可能地遵循[前后端接口规范 - RESTful版](https://zhuanlan.zhihu.com/p/508570164)。

## 生产环境

生产环境目前采用`docker-compose`编排方式部署，包含4个镜像，分别是
- `winterant/lduoj:1.2`; [Web端](https://github.com/winterant/OnlineJudge)，基于Ubuntu20.04镜像构建；暴露80端口；
- `winterant/judge:1.2`; [判题端](https://github.com/winterant/judge)，基于Ubuntu20.04镜像构建；
- `mysql:8.0`; 官方镜像；
- `redis:7.0`; 官方镜像；

编排启动的容器将以只读方式读取环境变量配置文件`lduoj.conf`。

详情参考[deploy分支](https://github.com/winterant/OnlineJudge/tree/deploy)。

## 本地开发

>开发前，请对[Laravel框架](https://learnku.com/docs/laravel/6.x)具备一定的了解。

本地开发最简单的方法是按照生产环境的部署方式部署到本地电脑，注意Web端要把`/app`挂载到本地，然后在本地打开该目录进行开发即可。

需要注意的问题：
- 修改配置文件需要使laravel框架重新加载才能生效；
  - 在容器内执行`php artisan opimize`使配置被重新加载。

如果希望向主仓库贡献代码，请先Fork[主仓库](https://github.com/winterant/OnlineJudge)，开发完成后向主仓库`master`分支发起`Pull Request`即可。
