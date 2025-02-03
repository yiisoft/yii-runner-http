# Yii Runner HTTP Change Log

## 3.1.0 under development

- Chg #85: Add `$temporaryErrorHandler` parameter to `HttpApplicationRunner` constructor. Mark parameter `$logger` and 
  method `withTemporaryErrorHandler()` as deprecated (@vjik)
- Enh #86: Raise the minimum PHP version to 8.1 and minor refactoring (@vjik)
- Chg #87: Change PHP constraint in `composer.json` to `~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0` (@vjik)

## 3.0.0 July 16, 2024

- New #80: Add ability to change the size of the buffer to send the content of the message body (@vjik)
- Chg #76: Allow to use any PSR logger, `NullLogger` by default (@vjik)
- Chg #77: Remove `ServerRequestFactory` (@vjik)
- Chg #77: Mark `SapiEmitter` as internal (@vjik)
- Bug #54: Fix incorrect handling response that did not close its own output buffers (@vjik)

## 2.3.0 March 10, 2024

- New #69: Add ability to set custom config merge plan file path, config and vendor directories (@vjik)
- Enh #58: Support stream output headers (@xepozz)
- Enh #64: Don't use buffered output in `SapiEmitter` when body size is less than buffer (@Gerych1984, @vjik)
- Enh #65: Add support for `psr/http-message` version `^2.0` (@vjik)

## 2.2.0 December 25, 2023

- New #49: Add ability to set custom config modifiers (@vjik)

## 2.1.0 July 10, 2023

- Chg #53: Add `RequestFactory` as a refactoring of `ServerRequestFactory`, mark `ServerRequestFactory` as deprecated (@vjik)
- Enh #50: Support stream output (@xepozz)

## 2.0.0 February 19, 2023

- New #40: In the `HttpApplicationRunner` constructor make parameter "environment" optional, default `null` (@vjik)
- New #42, #43: Add ability to configure all config group names (@vjik)
- New #43: Add parameter `$checkEvents` to `HttpApplicationRunner` constructor (@vjik)
- New #43: In the `HttpApplicationRunner` constructor make parameter "debug" optional, default `false` (@vjik)
- Chg #39, #43: Raise required version of `yiisoft/yii-runner` to `^2.0`, `yiisoft/log-target-file` to `^3.0`
  and `yiisoft/error-handler` to `^3.0` (@vjik)

## 1.1.2 November 10, 2022

- Enh #33: Add support for `yiisoft/definitions` version `^3.0` (@vjik)
- Bug #32: Add `psr/http-factory` and `psr/http-message` dependencies (@xepozz)

## 1.1.1 July 21, 2022

- Enh #27: Add support for `yiisoft/log-target-file` of version `^2.0` (@vjik)

## 1.1.0 June 17, 2022

- Chg #22: Raise packages version:`yiisoft/log` to `^2.0`, `yiisoft/log-target-file` to `^1.1` and 
  `yiisoft/error-handler` to `^2.1` (@rustamwin)

## 1.0.1 June 17, 2022

- Enh #23: Add support for `yiisoft/definitions` version `^2.0` (@vjik)

## 1.0.0 January 17, 2022

- Initial release.
