require "rails_helper"

RSpec.describe Loan do
  let(:book) { create_book(copies: 1) }

  it "defaults the due date to 14 days from today" do
    travel_to Date.new(2026, 3, 1) do
      expect(book.loans.create!(borrower_name: "Reader").due_on).to eq(Date.new(2026, 3, 15))
    end
  end

  it "refuses a loan when every copy is out" do
    book.loans.create!(borrower_name: "First")
    second = book.loans.create(borrower_name: "Second")
    expect(second).not_to be_persisted
    expect(second.errors[:book]).to eq([ "has no copies available" ])
  end

  it "frees the copy when returned" do
    book.loans.create!(borrower_name: "First").update!(returned_at: Time.current)
    expect(book.loans.create(borrower_name: "Second")).to be_persisted
  end

  it "limits the due date to today .. 60 days" do
    expect(book.loans.create(borrower_name: "R", due_on: Date.yesterday).errors[:due_on]).to eq([ "must be today or later" ])
    expect(book.loans.create(borrower_name: "R", due_on: Date.current + 61).errors[:due_on]).to eq([ "must be within 60 days" ])
    expect(book.loans.create(borrower_name: "", due_on: Date.current).errors[:borrower_name]).to be_present
  end

  it "is overdue only while unreturned after the due date" do
    loan = book.loans.create!(borrower_name: "Reader", due_on: Date.current + 1)
    travel 2.days do
      expect(loan).to be_overdue
      expect(Loan.overdue).to eq([ loan ])
      loan.update!(returned_at: Time.current)
      expect(loan).not_to be_overdue
      expect(Loan.overdue).to be_empty
    end
  end
end
