# 上海信息技术学校 团委学生会 宣传部 管理系统 后端git仓库

主要技术栈：
PHP 8.2.3 + Laravel 10

## 部署与项目更新

### 环境要求
操作系统不限

Nginx或Apache

composer 2.5.5

PHP 8.2.3 以及以下PHP拓展:

- Ctype PHP 扩展
- cURL PHP 扩展
- DOM PHP 扩展
- Fileinfo PHP 扩展
- Filter PHP 扩展
- Hash PHP 扩展
- Mbstring PHP 扩展
- OpenSSL PHP 扩展
- PCRE PHP 扩展
- PDO PHP 扩展
- Session PHP 扩展
- Tokenizer PHP 扩展
- XML PHP 扩展

***

## 项目API功能 To-do List


### AuthController 鉴权控制器

- [x] login 登录
- [x] logout 登出


### UserController

#### 用户功能
- [x] show 显示自己的信息
- [x] changePwd 修改密码

#### 管理员功能
- [x] index 列出所有人
- [x] store 添加用户
- [x] update 更新用户信息（含角色）
- [x] destroy 删除用户（软删除）
- [x] resetPwd 重置用户密码
- [x] batchStore 批量添加用户
- [ ] searchUserByName 通过姓名模糊搜索用户
- [ ] searchUserNotInActivityByName 通过姓名模糊搜索不在某个活动中的用户



### EquipmentController 设备控制器

#### 用户功能
- [x] showMyEquipment 显示所有设备，分为不同的状态：
    - 申请中、已归还、申请遭拒、申请延期、已延期、已上报的受损、已上报的丢失
- [x] indexUnassignedEquipments 列出空闲状态的设备
- [x] equipmentApply 设备申请
- [x] back 归还设备
- [x] delayApply 延期申报
- [x] reportEquipment 设备异常报告（丢失、损坏）

#### 管理员功能
- [x] index 列出所有未删除的设备
- [x] store 添加设备
- [x] show 显示某个设备详情
- [x] update 更新设备与设备状态，或手动将设备分配给用户
- [x] destroy 删除某个设备（软删除）
- [x] batchStore 使用csv批量添加设备
- [ ] indexApplicationList 列出审批列表
- [ ] agreeApplication 同意设备申请
- [ ] rejectApplication 拒绝设备申请
- [ ] indexDelayApplication 列出待延期申报
- [ ] indexAllDelayApplicationByERID 列出此设备申请的所有延期申报（通过租借ID）
- [ ] agreeEquipmentDelayApplication 同意延期
- [ ] rejectEquipmentDelayApplication 拒绝延期
- [ ] indexReports 列出主动上报的设备异常
- [ ] indexRentHistory 设备出借历史

### ActivityController 活动控制器

#### 用户功能
- [ ] listActivityByType 通过状态列出所有活动信息-
- [ ] EnrollActivity 报名活动

#### 管理员功能
- [ ] index 列出所有活动
- [ ] show 显示活动具体信息
- [ ] update 更新活动信息
- [ ] destroy 删除活动（软删除）
- [ ] updateActicityUser 更新活动人员
- [ ] listCheckIns 列出当前活动所有签到信息
- [ ] AgreeActivityEnrollments 同意报名
- [ ] RejectActivityEnrollments 拒绝报名

### CheckInController 签到控制器

#### 用户功能
- [ ] checkIn 签到

#### 管理员功能
- [ ] index 列出所有活动
- [ ] show 显示签到具体信息
- [ ] update 更新签到信息
- [ ] destroy 删除活动（软删除）
- [ ] revokeCheckInUser 撤销某人在某次签到中的签到行为
- [ ] GetCheckInUserInfo 查看某人在某次签到中的具体照片


### MessageController 消息控制器
- [ ] indexAllMsg
- [ ] getCheckIningMsg
- [ ] getCheckInRevokedMsg
- [ ] getNewActivityMsg
- [ ] getAgreedActivityEnrollmentMsg
- [ ] getRejectedActivityEnrollmentMsg
- [ ] getAssignedEquipmentMsg
- [ ] getRejectedEquipmentMsg


***

本项目遵循MIT开源许可。