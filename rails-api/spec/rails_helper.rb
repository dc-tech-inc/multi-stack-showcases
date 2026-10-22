require "spec_helper"
ENV["RAILS_ENV"] ||= "test"
require_relative "../config/environment"
abort("The Rails environment is running in production mode!") if Rails.env.production?
require "rspec/rails"

begin
  ActiveRecord::Migration.maintain_test_schema!
rescue ActiveRecord::PendingMigrationError => e
  abort e.to_s.strip
end

module SpecHelpers
  # Builds a book with a valid ISBN-13 (check digit computed) unless one is given.
  def create_book(**attrs)
    @isbn_seq = (@isbn_seq || 0) + 1
    base = format("97910000%04d", @isbn_seq)
    sum = base.each_char.with_index.sum { |d, i| d.to_i * (i.even? ? 1 : 3) }
    Book.create!({ title: "Book #{@isbn_seq}", author: "Author", isbn: "#{base}#{(10 - sum % 10) % 10}", copies: 1 }.merge(attrs))
  end

  def json = response.parsed_body
end

RSpec.configure do |config|
  config.use_transactional_fixtures = true
  config.infer_spec_type_from_file_location!
  config.filter_rails_from_backtrace!
  config.include SpecHelpers
  config.include ActiveSupport::Testing::TimeHelpers
end
