# Development image: lets you run the API without installing Ruby.
FROM ruby:4.0.8-slim

RUN apt-get update -qq \
 && apt-get install -y --no-install-recommends build-essential libyaml-dev \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /app
ENV RAILS_ENV=development BUNDLE_WITHOUT=test

COPY Gemfile Gemfile.lock .ruby-version ./
RUN bundle install

COPY . .
RUN useradd --create-home rails && mkdir -p tmp log storage && chown -R rails:rails tmp log storage
USER rails

EXPOSE 3000
CMD ["sh", "-c", "bin/rails db:prepare && bin/rails db:seed && exec bin/rails server -b 0.0.0.0"]
