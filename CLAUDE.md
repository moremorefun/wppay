# CLAUDE.md

## 项目概述

PayTheFly Crypto Gateway - WordPress 加密货币捐赠插件

## 开发环境

### wp-env

使用 `@wordpress/env` 作为本地开发环境。

```bash
pnpm start    # 启动环境
pnpm stop     # 停止环境
pnpm destroy  # 销毁环境
```

### 插件路径

在 wp-env 容器中，插件挂载路径为：
- `/var/www/html/wp-content/plugins/wppay`

**注意**：目录名是 `wppay`（项目文件夹名），不是 `paythefly`。

## 测试

```bash
# JS 测试 (Vitest)
pnpm test

# PHP 测试 (PHPUnit via wp-env)
npx wp-env run tests-cli phpunit -- --configuration=/var/www/html/wp-content/plugins/wppay/phpunit.xml

# E2E 测试 (Playwright)
pnpm test:e2e
```

## 代码规范

- PHP: WordPress Coding Standards (PHPCS)
- JS/TS: ESLint + Prettier
- CSS: Stylelint

```bash
pnpm lint       # 运行所有 lint
pnpm lint:fix   # 修复 lint 问题
```

## 构建

```bash
pnpm build              # 构建前端资源
node scripts/zip.js     # 打包成 zip (build/paythefly-crypto-gateway.zip)
```

## 版本

更新版本时需要修改以下文件：
- `paythefly.php` (Plugin header + PAYTHEFLY_VERSION constant)
- `readme.txt` (Stable tag + Changelog)
- `tests/php/PayTheFlyTest.php` (version assertion)
