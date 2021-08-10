[![codecov](https://codecov.io/gh/el7cosmos/social_auth_discourse/branch/main/graph/badge.svg?token=lkC0Ugf1t6)](https://codecov.io/gh/el7cosmos/social_auth_discourse)

## INSTALLATION

### Add module to composer requirements
```shell
composer require el7cosmos/social_auth_discourse
```

## CONFIGURATION

### Enable DiscourseConnect provider setting
Under Discourse admin site settings (`/admin/site_settings/category/login`) enable setting `enable discourse connect provider` and add a secret string to `discourse connect provider secrets`.

![DiscourseConnect settings](settings.png)

The provided secret must match with the secret in Drupal configuration.

### Configure Drupal Social Auth Discourse settings
Configure the social auth discourse module in `Administration » Configuration » Social API settings » User authentication » Discourse` or via URL `admin/config/social-api/social-auth/discourse`

Fill the `Secret` setting with the same value as the discourse setting above.
