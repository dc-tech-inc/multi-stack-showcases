class BooksController < ApplicationController
  before_action :set_book, only: %i[show update destroy]

  # GET /books?q=&limit=&offset=
  def index
    books = Book.search(params[:q])
    limit, offset = page_params
    render json: { items: books.with_availability.order(:title, :id).limit(limit).offset(offset), total: books.count }
  end

  def show
    render json: @book
  end

  def create
    book = Book.new(book_params)
    if book.save
      render json: book, status: :created, location: book
    else
      render_invalid book
    end
  end

  def update
    if @book.update(book_params)
      render json: @book
    else
      render_invalid @book
    end
  end

  def destroy
    if @book.destroy
      head :no_content
    else
      render_error :conflict, @book.errors.full_messages.to_sentence
    end
  end

  private

  def set_book
    @book = Book.find(params[:id])
  end

  def book_params
    params.expect(book: %i[title author isbn copies])
  end
end
