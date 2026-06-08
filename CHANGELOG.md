# Changelog

## [1.3.0](https://github.com/Dubbie/sign-vault/compare/v1.2.0...v1.3.0) (2026-06-08)


### Features

* **engagement:** add public engagement analytics with admin dashboard ([4cd00e5](https://github.com/Dubbie/sign-vault/commit/4cd00e5c62cba8883f72c68af5ecbbedaf29eeec))
* **frontend:** add avatar fallbacks ([e446f4b](https://github.com/Dubbie/sign-vault/commit/e446f4b92250261ddee10e94fb66b7b708f6cdb7))
* **frontend:** add catch-all not found page ([3fff227](https://github.com/Dubbie/sign-vault/commit/3fff227b074f60fc7fee09d1a91ef2b7d0e5ee34))
* **signs:** generate WebP thumbnails for sign previews ([1acbf67](https://github.com/Dubbie/sign-vault/commit/1acbf67e0326c11b383b5c9a72c55321110d25cc))


### Bug Fixes

* **explore:** default public folders to top sorting ([7247cf4](https://github.com/Dubbie/sign-vault/commit/7247cf4c3f9e7662f31e671ea72f40c0ed4c1686))
* **explore:** default to top sort ([4603d1a](https://github.com/Dubbie/sign-vault/commit/4603d1abcdbe7f5356f9fdc4c61e30ceeabec59e))
* **frontend:** tighten navbar avatar trigger sizing ([09b73d4](https://github.com/Dubbie/sign-vault/commit/09b73d4d5b36ea64687dfd2c6d6cd57d89f31323))


### Performance Improvements

* **api:** optimize public folder browse queries ([8494633](https://github.com/Dubbie/sign-vault/commit/84946336b9a94d97402581a1501f30d16c2fdfd0))

## [1.2.0](https://github.com/Dubbie/sign-vault/compare/v1.1.0...v1.2.0) (2026-06-07)


### Features

* **explore:** expand pagination, grow preview grid, and add sticky preview panel ([34ce624](https://github.com/Dubbie/sign-vault/commit/34ce624474e8bc8954800e4b5874e67b426c6c97))
* **frontend:** make the site usable on mobile for admins ([f0d9d6b](https://github.com/Dubbie/sign-vault/commit/f0d9d6b5581fbd8098073c0e4703e2af9689789d))
* **signs:** upload large sign selections in batches ([c448196](https://github.com/Dubbie/sign-vault/commit/c448196feffd45629c83e30080223a704df5476c))


### Bug Fixes

* **activity-log:** group batched sign uploads ([6508f95](https://github.com/Dubbie/sign-vault/commit/6508f95569bc16f274c0e1e13f45185ee2de96d4))
* **frontend:** align vote button inline on public folder page ([b734a13](https://github.com/Dubbie/sign-vault/commit/b734a1334bc16d6e43b171edefef222a9317448a))
* **frontend:** match release-please linked headings in changelog parser ([65edfec](https://github.com/Dubbie/sign-vault/commit/65edfec4e1453d47d380e4325213a3223f0fee15))

## [1.1.0](https://github.com/Dubbie/sign-vault/compare/v1.0.0...v1.1.0) (2026-06-07)


### Features

* **folders:** add variant grid background presets ([6a9de14](https://github.com/Dubbie/sign-vault/commit/6a9de142ccc7b9d4897a5f22e77f0a8970db4cc3))
* **meta:** add social preview metadata ([4d2a74c](https://github.com/Dubbie/sign-vault/commit/4d2a74ce5534b06ddc56ef0559b6f16cfa934a00))


### Bug Fixes

* **ci:** run staged workspace tools correctly ([192a161](https://github.com/Dubbie/sign-vault/commit/192a1611500d791e6387aeff09740bd0f09d5ead))
* **explore:** prevent folder name descender clipping ([f2d1fce](https://github.com/Dubbie/sign-vault/commit/f2d1fcee7a0e638b51925796dab2cfbe4a8ec634))
* **frontend:** polish public folder vote buttons ([155dc31](https://github.com/Dubbie/sign-vault/commit/155dc31083074db9ecee2ca833b050ebc250a082))
* **frontend:** render formatted release notes ([303c9d9](https://github.com/Dubbie/sign-vault/commit/303c9d9258855e705c14b39aee99730cd7b571a8))

## [1.0.0] (2026-06-06)

### Features

* initial SignVault release baseline
* Vue frontend, landing page, and Laravel API shipped from one repo-wide version
* public folder browsing, OAuth login, admin tooling, and folder voting available in the first tracked release
