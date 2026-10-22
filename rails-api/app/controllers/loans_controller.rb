class LoansController < ApplicationController
  STATUSES = %w[active overdue returned all].freeze

  # GET /loans?status=active|overdue|returned|all (default active)
  def index
    status = params.fetch(:status, "active")
    return render_error(:bad_request, "status must be one of: #{STATUSES.join(", ")}") unless STATUSES.include?(status)

    loans = status == "all" ? Loan.all : Loan.public_send(status)
    limit, offset = page_params
    render json: { items: loans.includes(:book).order(:due_on, :id).limit(limit).offset(offset), total: loans.count }
  end

  # POST /books/:book_id/loans
  def create
    book = Book.find(params[:book_id])
    # Row lock + transaction so two requests cannot both take the last copy.
    # ponytail: SQLite serialises writers anyway; on PostgreSQL this becomes SELECT ... FOR UPDATE.
    loan = book.with_lock { book.loans.create(loan_params) }
    if loan.persisted?
      render json: loan, status: :created
    else
      render_invalid loan
    end
  end

  # PATCH /loans/:id/return
  def mark_returned
    loan = Loan.find(params[:id])
    return render_error(:conflict, "Loan was already returned") if loan.returned_at

    loan.update!(returned_at: Time.current)
    render json: loan
  end

  private

  def loan_params
    params.expect(loan: %i[borrower_name due_on])
  end
end
