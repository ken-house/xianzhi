const fs = require('fs-extra');
const path = require('path');
const UglifyJsPlugin = require('terser-webpack-plugin');

// 获取base.scss文件
const _antdCssStrTemp = fs.readFileSync('src/assets/style/include/base.scss', 'utf-8').toString().split('// antdend')[0].match(/\$(.*);/g).join(',').replace(/;,/g, '",').replace(/;/g, '"').replace(/: /g, '": "').replace(/\$/g, '"');
const _antdCssData = JSON.parse('{' + _antdCssStrTemp + '}');

// 项目路径配置
const projectConfig = {
    projectName: __dirname.split('/')[__dirname.split('/').length - 1], // 项目名
    viewsDir: path.resolve('', '../../views') // 打包基本路径(package.json相对views文件夹)
};

Object.assign(projectConfig, {
    indexPath: path.resolve(projectConfig.viewsDir, `view.${projectConfig.projectName}/backend/views/site/index.php`), // 项目入口文件
    outputDir: path.resolve(projectConfig.viewsDir, `view.${projectConfig.projectName}/backend/web`), // 项目打包输出目录
    assetsDir: 'static' // 静态文件输出目录
});
if (process.env.NODE_ENV === 'production') {
    const paths = path.resolve(projectConfig.viewsDir, `view.${projectConfig.projectName}`);
    const files = fs.readdirSync(paths);
    files.forEach(el => {
        if (el != '.git') {
            fs.removeSync(path.join(paths, el));
        }
    });
}

function resolve(dir) {
    return path.join(__dirname, dir);
}

module.exports = {
    indexPath: projectConfig.indexPath,
    // npm run build打包输出目录
    outputDir: projectConfig.outputDir,
    // 打包静态文输出目录
    assetsDir: projectConfig.assetsDir,
    productionSourceMap: false,
    // 保存时，是否自动代码格式校验
    lintOnSave: false,
    // 定义antd-design-vue的全局基本样式
    css: {
        loaderOptions: {
            less: {
                // modifyVars: {
                //     'primary-color': '#f00',
                //     'link-color': '#f00',
                //     'border-radius-base': '2px',
                // },
                modifyVars: _antdCssData,
                javascriptEnabled: true
            }
        }
    },
    devServer: {
        host: '0.0.0.0',
        port: process.env.NODE_ENV.port, // 端口号
        disableHostCheck: true,
        https: false, // https:{type:Boolean}
        open: true, // 配置自动启动浏览器  http://XXX.XXX.X.XX:7071/rest/XXX/
        hotOnly: true, // 热更新
        proxy: {
            '/api': {
                target: 'http://admin.xiaozhatravel.top',
                changeOrigin: true,
                secure: false,
                pathRewrite: {
                    '^/api': ''
                }
            }
        }
    },
    pwa: {
        name: 'vue-mange-template',
        themeColor: '#000000'
        // iconPaths: {
        //     favicon32: 'favicon.ico',
        //     favicon16: 'favicon.ico',
        //     appleTouchIcon: 'favicon.ico',
        //     maskIcon: 'favicon.ico',
        //     msTileImage: 'favicon.ico'
        // }
    },

    chainWebpack: (config) => {
        // 配置路径别名
        config.resolve.alias
            .set('@', resolve('src'));

        /**
     * 无需使用@import在每个scss文件中引入变量或者mixin，也可以避免大量@import导致build变慢
     * sass-resources-loader 文档链接：https://github.com/shakacode/sass-resources-loader
     */
        const oneOfsMap = config.module.rule('scss').oneOfs.store;
        const sassResources = ['include/base.scss', 'include/mixin.scss']; // scss资源文件，可以在里面定义变量，mixin,全局混入样式等
        oneOfsMap.forEach(item => {
            item
                .use('sass-resources-loader')
                .loader('sass-resources-loader')
                .options({
                    resources: sassResources.map(file => path.resolve(__dirname, 'src/assets/style/' + file))
                })
                .end();
        });
    },
    configureWebpack: config => {
        const myConfig = {};
        if (process.env.NODE_ENV === 'production') {
            // 生产环境自动删除console
            config.plugins.push(
                new UglifyJsPlugin({
                    terserOptions: {
                        compress: {
                            drop_debugger: true,
                            drop_console: true
                        },
                        warnings: false
                    },
                    parallel: true // 使用多进程打包提高构建速度
                })
            );
        }
        // if (process.env.NODE_ENV === 'development') {}
        return myConfig;
    }
};
