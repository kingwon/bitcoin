<?php
/**
 * 路由
 */

// 首页
respond('/', function ($request, $response, $app) {
    $c = new indexController($request, $response, $app);
    $c->indexAction();
});

// 登录页
respond('GET', '/login' , function ($request, $response, $app) {
    $c = new userController($request, $response, $app);
    $c->loginViewAction();
});

// 注册页
respond('GET', '/register' , function ($request, $response, $app) {
    $c = new userController($request, $response, $app);
    $c->registerViewAction();
});

// 我的账户-我的买单
respond('/my/buy', function ($request, $response, $app) {
    $c = new postController($request, $response, $app);
    $c->listUserBuyAction();
});

// 我的账户-我的卖单
respond('/my/sell', function ($request, $response, $app) {
    $c = new postController($request, $response, $app);
    $c->listUserSellAction();
});

// 我的账户-交易历史
respond('/my/history', function ($request, $response, $app) {
    $c = new tradeController($request, $response, $app);
    $c->listUserAction();
});

// 我的账户-人民币充值历史
respond('/my/cny', function ($request, $response, $app) {
    $c = new accountController($request, $response, $app);
    $c->listCnyUserAction();
});

// 我的账户-BTC充值历史
respond('/my/btc', function ($request, $response, $app) {
    $c = new accountController($request, $response, $app);
    $c->listBtcUserAction();
});

// 充人民币
respond('GET', '/push/cny' , function ($request, $response, $app) {
    $c = new accountController($request, $response, $app);
    $c->pushCnyViewAction();
});

// 充人民币
respond('POST', '/push/cny', function ($request, $response, $app) {
    $c = new accountController($request, $response, $app);
    $c->pushCnyAction();
});

// 提人民币
respond('GET', '/pull/cny' , function ($request, $response, $app) {
    $c = new accountController($request, $response, $app);
    $c->pullCnyViewAction();
});

// 提人民币
respond('POST', '/pull/cny', function ($request, $response, $app) {
    $c = new accountController($request, $response, $app);
    $c->pullCnyAction();
});

// 充BTC
respond('GET', '/push/btc' , function ($request, $response, $app) {
    $c = new accountController($request, $response, $app);
    $c->pushBtcViewAction();
});

// 充BTC
respond('POST', '/push/btc', function ($request, $response, $app) {
    $c = new accountController($request, $response, $app);
    $c->pushBtcAction();
});

// 提BTC
respond('GET', '/pull/btc' , function ($request, $response, $app) {
    $c = new accountController($request, $response, $app);
    $c->pullBtcViewAction();
});

// 提BTC
respond('POST', '/pull/btc', function ($request, $response, $app) {
    $c = new accountController($request, $response, $app);
    $c->pullBtcAction();
});

// 新挂卖单
respond('GET', '/sell' , function ($request, $response, $app) {
    $c = new postController($request, $response, $app);
    $c->addSellViewAction();
});

// 新挂卖单
respond('POST', '/sell' , function ($request, $response, $app) {
    $c = new postController($request, $response, $app);
    $c->addSellAction();
});

// 新挂买单
respond('GET', '/buy' , function ($request, $response, $app) {
    $c = new postController($request, $response, $app);
    $c->addBuyViewAction();
});

// 新挂买单
respond('POST', '/buy' , function ($request, $response, $app) {
    $c = new postController($request, $response, $app);
    $c->addBuyAction();
});

// 仅供测试，可以删除
respond('/btc_test', function ($request, $response, $app) {
    $c = new tradeController($request, $response, $app);
    $c->btc_testAction();
});

// 后台页面
with('/admin', function () {

    // 买单列表
    respond('/buy', function ($request, $response, $app) {
        $c = new postController($request, $response, $app);
        $c->listBuyAction();
    });

    // 卖单列表
    respond('/sell', function ($request, $response, $app) {
        $c = new postController($request, $response, $app);
        $c->listSellAction();
    });

    // 交易历史
    respond('/history', function ($request, $response, $app) {
        $c = new tradeController($request, $response, $app);
        $c->listAction();
    });

    // 充钱历史
    respond('/cny', function ($request, $response, $app) {
        $c = new accountController($request, $response, $app);
        $c->listCnyAction();
    });

    // 充BTC历史
    respond('/btc', function ($request, $response, $app) {
        $c = new accountController($request, $response, $app);
        $c->listBtcAction();
    });

    // 用户列表
    respond('/user', function ($request, $response, $app) {
        $c = new userController($request, $response, $app);
        $c->listAction();
    });

    // 修改密码
    respond('GET', '/password', function ($request, $response, $app) {
        $c = new userController($request, $response, $app);
        $c->passwordViewAction();
    });

    // 修改密码
    respond('POST', '/password', function ($request, $response, $app) {
        $c = new userController($request, $response, $app);
        $c->passwordAction();
    });

    // 创建管理员
    respond('GET', '/create', function ($request, $response, $app) {
        $c = new userController($request, $response, $app);
        $c->createAdminViewAction();
    });

    // 创建管理员
    respond('POST', '/create', function ($request, $response, $app) {
        $c = new userController($request, $response, $app);
        $c->createAdminAction();
    });

    // 管理员列表
    respond('/list', function ($request, $response, $app) {
        $c = new userController($request, $response, $app);
        $c->listAdminAction();
    });
});

