require "rails_helper"

RSpec.describe "Loans API" do
  let(:book) { create_book(title: "Loanable", copies: 1) }

  describe "POST /books/:book_id/loans" do
    it "lends a copy" do
      post "/books/#{book.id}/loans", params: { loan: { borrower_name: "Avery" } }, as: :json
      expect(response).to have_http_status(:created)
      expect(json).to include("book_id" => book.id, "book_title" => "Loanable", "borrower_name" => "Avery", "returned_at" => nil, "overdue" => false)
      expect(json["due_on"]).to eq((Date.current + 14).iso8601)
    end

    it "returns 422 when no copy is left" do
      book.loans.create!(borrower_name: "First")
      post "/books/#{book.id}/loans", params: { loan: { borrower_name: "Second" } }, as: :json
      expect(response).to have_http_status(:unprocessable_content)
      expect(json["error"]["details"]).to eq("book" => [ "has no copies available" ])
    end

    it "returns 404 for an unknown book" do
      post "/books/0/loans", params: { loan: { borrower_name: "Avery" } }, as: :json
      expect(response).to have_http_status(:not_found)
    end
  end

  describe "PATCH /loans/:id/return" do
    it "returns a loan once" do
      loan = book.loans.create!(borrower_name: "Avery")
      patch "/loans/#{loan.id}/return"
      expect(response).to have_http_status(:ok)
      expect(json["returned_at"]).to be_present

      patch "/loans/#{loan.id}/return"
      expect(response).to have_http_status(:conflict)
      expect(json["error"]["message"]).to eq("Loan was already returned")
    end
  end

  describe "GET /loans" do
    before do
      other = create_book(copies: 3)
      @late = other.loans.create!(borrower_name: "Late", due_on: Date.current + 1)
      @current = other.loans.create!(borrower_name: "Current", due_on: Date.current + 10)
      @done = other.loans.create!(borrower_name: "Done", returned_at: Time.current)
    end

    it "filters by status" do
      travel 3.days do
        get "/loans"
        expect(json["items"].map { it["borrower_name"] }).to eq(%w[Late Current])
        expect(json["items"].first["overdue"]).to be(true)

        get "/loans", params: { status: "overdue" }
        expect(json["items"].map { it["borrower_name"] }).to eq(%w[Late])

        get "/loans", params: { status: "returned" }
        expect(json["items"].map { it["borrower_name"] }).to eq(%w[Done])

        get "/loans", params: { status: "all" }
        expect(json["total"]).to eq(3)
      end
    end

    it "rejects an unknown status" do
      get "/loans", params: { status: "lost" }
      expect(response).to have_http_status(:bad_request)
    end
  end

  it "reports health at /up" do
    get "/up"
    expect(response).to have_http_status(:ok)
  end
end
