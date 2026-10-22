require_relative "boot"

require "rails"
# Only the frameworks an API needs: no views, mailers, jobs, cable or storage.
require "active_model/railtie"
require "active_record/railtie"
require "action_controller/railtie"

Bundler.require(*Rails.groups)

module LibraryApi
  class Application < Rails::Application
    config.load_defaults 8.1
    config.api_only = true
  end
end
