require "rails_helper"

RSpec.describe "Books API" do
  describe "GET /books" do
    it "pages, sorts by title and reports the total and availability" do
      %w[Cedar Birch Aspen].each { |title| create_book(title:, copies: 2) }
      Book.find_by!(title: "Birch").loans.create!(borrower_name: "Reader")

      get "/books", params: { limit: 2, offset: 0 }
      expect(response).to have_http_status(:ok)
      expect(json["total"]).to eq(3)
      expect(json["items"].map { it["title"] }).to eq(%w[Aspen Birch])
      expect(json["items"].map { it["available_copies"] }).to eq([ 2, 1 ])

      get "/books", params: { limit: 2, offset: 2 }
      expect(json["items"].map { it["title"] }).to eq(%w[Cedar])
    end

    it "searches and clamps bad paging values" do
      create_book(title: "Harbor Lights", author: "A")
      create_book(title: "Other", author: "B")
      get "/books", params: { q: "harbor", limit: "lots", offset: -5 }
      expect(json["items"].map { it["title"] }).to eq([ "Harbor Lights" ])
      expect(json["total"]).to eq(1)
    end
  end

  it "shows a book or a 404 in the standard error shape" do
    book = create_book(title: "Shown")
    get "/books/#{book.id}"
    expect(json).to include("title" => "Shown", "available_copies" => 1)

    get "/books/0"
    expect(response).to have_http_status(:not_found)
    expect(json).to eq("error" => { "status" => 404, "message" => "Book not found" })
  end

  describe "POST /books" do
    it "creates a book" do
      post "/books", params: { book: { title: "New", author: "Writer", isbn: "979-8-00-000003-8", copies: 2 } }, as: :json
      expect(response).to have_http_status(:created)
      expect(response.headers["Location"]).to end_with("/books/#{json["id"]}")
      expect(json).to include("isbn" => "9798000000038", "available_copies" => 2)
    end

    it "returns 422 with field errors" do
      post "/books", params: { book: { title: "", author: "W", isbn: "9798000000039" } }, as: :json
      expect(response).to have_http_status(:unprocessable_content)
      expect(json["error"]["details"]).to include("title" => [ "can't be blank" ], "isbn" => [ "has an invalid check digit" ])
    end

    it "returns 400 when the book key is missing" do
      post "/books", params: { title: "No wrapper" }.to_json, headers: { "CONTENT_TYPE" => "text/plain" }
      expect(response).to have_http_status(:bad_request)
      expect(json["error"]["message"]).to match(/book/)
    end
  end

  it "updates a book and ignores unknown attributes" do
    book = create_book(copies: 1)
    patch "/books/#{book.id}", params: { book: { copies: 5, id: 999 } }, as: :json
    expect(response).to have_http_status(:ok)
    expect(book.reload.copies).to eq(5)
    expect(json["id"]).to eq(book.id)
  end

  it "rejects an update below the copies on loan" do
    book = create_book(copies: 2)
    2.times { book.loans.create!(borrower_name: "Reader") }
    patch "/books/#{book.id}", params: { book: { copies: 1 } }, as: :json
    expect(response).to have_http_status(:unprocessable_content)
  end

  describe "DELETE /books/:id" do
    it "deletes a book without loans" do
      book = create_book
      delete "/books/#{book.id}"
      expect(response).to have_http_status(:no_content)
      expect(Book.exists?(book.id)).to be(false)
    end

    it "returns 409 for a book with loan history" do
      book = create_book
      book.loans.create!(borrower_name: "Reader")
      delete "/books/#{book.id}"
      expect(response).to have_http_status(:conflict)
      expect(json["error"]["message"]).to match(/loans/i)
    end
  end
end
