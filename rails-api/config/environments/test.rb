Rails.application.configure do
  config.enable_reloading = false
  # Eager load in CI so every file is at least loaded once.
  config.eager_load = ENV["CI"].present?
  config.consider_all_requests_local = true
  config.cache_store = :null_store

  # Render error responses (404, 422...) instead of raising, like production.
  config.action_dispatch.show_exceptions = :rescuable
  config.action_controller.allow_forgery_protection = false
  config.action_controller.raise_on_missing_callback_actions = true

  config.active_support.deprecation = :stderr
end
