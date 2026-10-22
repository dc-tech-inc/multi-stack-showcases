require "rails_helper"

RSpec.describe Book do
  it "accepts a valid ISBN-13 with dashes and stores digits only" do
    book = create_book(isbn: "979-8-00-000001-4")
    expect(book.isbn).to eq("9798000000014")
  end

  it "rejects a wrong check digit, a wrong prefix and a duplicate" do
    create_book(isbn: "9798000000014")
    expect(Book.new(title: "T", author: "A", isbn: "9798000000015").tap(&:validate).errors[:isbn]).to include("has an invalid check digit")
    expect(Book.new(title: "T", author: "A", isbn: "1234567890123").tap(&:validate).errors[:isbn]).to include("must be 13 digits starting with 978 or 979")
    expect(Book.new(title: "T", author: "A", isbn: "9798000000014").tap(&:validate).errors[:isbn]).to include("has already been taken")
  end

  it "requires title, author and a sane number of copies" do
    book = Book.new(isbn: "9798000000021", copies: -1)
    expect(book).not_to be_valid
    expect(book.errors.attribute_names).to include(:title, :author, :copies)
    expect(Book.new(title: "T", author: "A", isbn: "9798000000021", copies: 1.5)).not_to be_valid
  end

  it "computes available copies from active loans" do
    book = create_book(copies: 2)
    book.loans.create!(borrower_name: "Reader One")
    book.loans.create!(borrower_name: "Reader Two", returned_at: Time.current)
    expect(book.available_copies).to eq(1)
    expect(Book.with_availability.find(book.id).available_copies).to eq(1)
  end

  it "cannot drop below the copies currently on loan" do
    book = create_book(copies: 2)
    2.times { |i| book.loans.create!(borrower_name: "Reader #{i}") }
    expect(book.update(copies: 1)).to be(false)
    expect(book.errors[:copies]).to eq([ "cannot be less than the 2 copies on loan" ])
  end

  it "searches title and author, treating % and _ literally" do
    create_book(title: "Rivers of Light", author: "Ann Doe")
    create_book(title: "Mountains", author: "River Smith")
    create_book(title: "100% Cotton", author: "X")
    expect(Book.search("river").pluck(:title)).to contain_exactly("Rivers of Light", "Mountains")
    expect(Book.search("%").pluck(:title)).to eq([ "100% Cotton" ])
    expect(Book.search(" ").count).to eq(3)
  end

  it "keeps books that have loan history" do
    book = create_book
    book.loans.create!(borrower_name: "Reader", returned_at: Time.current)
    expect(book.destroy).to be(false)
    expect(Book.exists?(book.id)).to be(true)
  end
end
