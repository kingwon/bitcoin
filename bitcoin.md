撮合算法
连续竞价的原则：价格优先，时间优先
卖出价低于买入价
我们就按照最简单，也是最公平的一种机制吧，就是取平均价格。

发起人-小草  15:24:47
网站交易增加一个开关项：就是我们需要维护服务器的时候，就关闭交易，同时前台会提醒用户不要担心我们正在维护服务器
因为这种金融交易类的，万一我们需要维护，开始没考虑到这个，到时候需要改动解析之类的就烦了


多台MYSQL读写分离同步延时引起前台读数据异常的问题


发起人-小草  16:49:03
还有个注意点
之前他们平台碰到了小数点攻击
你看成交记录的数据就知道了
这个可能涉及到浮点运算方面的限制问题
发起人-小草  16:50:14
现在有的平台就直接限制只允许发布>=0.01的数额交易


数值方面你考虑下，有点烦的，因为还涉及到我们的手续费设定，比如我们设置0.2%的手续费

安全提示：如果聊天中有涉及财产的操作，请一定先核实好友身份。发送验证问题或点击举报
小池·水  17:02:30
嗯
发起人-小草  17:02:34
那么假如只允许0.01的交易，但是经过我们的手续费以扣
还是会出现0.008
然后在和数量、价格一相乘
那位数又大了
发起人-小草  17:03:47
再则，在发布买卖单的时候，比如有买家发布0.001，那肯定没办法给他成交的，因为我们的手续费就要0.002

有如下设置：
允许发单的最小BTC额度 min_btc
允许发单的最小价格 min_price
我们系统的手续费 ratio
我们所允许的最小的小数 min_float

三者需要满足 (min_btc * min_price * ration) >= min_float
我们在设置的时候检测，如果不满足，就提醒管理员，可能超过最小的小数位数


允许他购买这么多
但是不能交易的
小池·水  17:32:55
。。。
发起人-小草  17:33:21
他购买过去了，只能放在账户里看
小池·水  17:33:26
。。。
发起人-小草  17:33:43
非得有下次再购买凑齐到0.001才可以再次做出售的动作


我想，我们可以分两种情况来，到时候我们程序也做下限制：1，少额比特币充值1-10可以程序自动交易
2，大额比特币充值与提现比如几百上千用人工操作。


我和你说下原理：比特币要实现自动化，那么比特币程序必须对外网开放端口，24小时不停的接受查询，这样才可以自动化，也就是这个比特币账户会24小时暴露在互联网上

我想这样来设计，分两个账户，小额账户和大额账户：
小额账户我们把这个比特币服务端程序架设到互联网上，提供我们自己的程序来检测查询服务
大额账户，我们专门弄一台电脑私有化它，不提供对外的相关服务
只做人工检查
小池·水  10:53:11
可以
发起人-小草  10:53:27
有大额单子进入大额账户我们才联网来查看是否到账，到账之后把钱冲入网站里客户帐号里
小池·水  10:54:32
嗯
发起人-小草  10:54:33
另外小额账户的那个我们单独租用一台服务器放到国内，比如阿里云上去，通过云控制平台防火墙功能关闭除比特币程序服务要求开放的端口之外的一切端口
比如我们初期是1-10个，但一段时间后可以适当到1-50哥
