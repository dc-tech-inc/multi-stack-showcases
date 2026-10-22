class Book < ApplicationRecord
  # Books with loan history are kept for the record; destroy fails instead.
  has_many :loans, dependent: :restrict_with_error

  normalizes :isbn, with: ->(isbn) { isbn.to_s.delete("- ") }

  validates :title, :author, presence: true, length: { maximum: 200 }
  validates :isbn, presence: true, uniqueness: true, format: { with: /\A97[89]\d{10}\z/, message: "must be 13 digits starting with 978 or 979" }
  validates :copies, numericality: { only_integer: true, in: 0..1000 }
  validate :isbn_check_digit
  validate :copies_cover_active_loans, on: :update

  scope :search, ->(q) {
    next all if q.blank?

    # sanitize_sql_like escapes % and _ with a backslash; SQLite only honours it with ESCAPE.
    pattern = "%#{sanitize_sql_like(q.strip)}%"
    where("title LIKE :pattern ESCAPE '\\' OR author LIKE :pattern ESCAPE '\\'", pattern:)
  }

  # Adds active_loans_count with a correlated subquery, so a page of books
  # does not run one COUNT per book.
  scope :with_availability, -> {
    select("books.*", "(SELECT COUNT(*) FROM loans WHERE loans.book_id = books.id AND loans.returned_at IS NULL) AS active_loans_count")
  }

  def active_loans
    has_attribute?(:active_loans_count) ? self[:active_loans_count] : loans.active.count
  end

  def available_copies = copies - active_loans

  def as_json(*)
    { id:, title:, author:, isbn:, copies:, available_copies: }
  end

  private

  # ISBN-13: weights 1,3,1,3... over the first 12 digits; the 13th makes the sum a multiple of 10.
  def isbn_check_digit
    return unless isbn.to_s.match?(/\A\d{13}\z/)

    sum = isbn[0, 12].each_char.with_index.sum { |digit, i| digit.to_i * (i.even? ? 1 : 3) }
    errors.add(:isbn, "has an invalid check digit") unless (10 - sum % 10) % 10 == isbn[-1].to_i
  end

  def copies_cover_active_loans
    return unless copies_changed? && copies.is_a?(Integer)

    on_loan = loans.active.count
    errors.add(:copies, "cannot be less than the #{on_loan} copies on loan") if copies < on_loan
  end
end
