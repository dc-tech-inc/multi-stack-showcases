Rails.application.routes.draw do
  # 200 when the app boots without exceptions; used by the Docker smoke test.
  get "up" => "rails/health#show", as: :rails_health_check

  resources :books do
    resources :loans, only: :create
  end

  resources :loans, only: :index do
    patch :return, on: :member, action: :mark_returned
  end
end
