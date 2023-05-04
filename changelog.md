# ⌬ LiquidDesign/Base - CHANGELOG

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0-beta.1]

### Added

- Shop entity to use multiple Shops on same database.
- Service *ShopsConfig*
  - Use *ShopsConfigDI* to register this service. 
  - Use this service exclusively to get available Shops or currently selected Shop 
- Helper classes to create entities:
  - *ShopEntity*
  - *ShopEntityTrait*
  - *ShopSystemicEntity*
  - *SystemicEntityTrait*
- *GeneralRepositoryHelpers*
  - Functions to help with selection of entities in admin
### Changed

- **BREAKING:** PHP version 8.2 or higher is required

### Removed

### Deprecated

### Fixed