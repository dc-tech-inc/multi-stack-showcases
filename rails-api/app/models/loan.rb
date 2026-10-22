class Loan < ApplicationRecord
  DEFAULT_DAYS = 14
  MAX_DAYS = 60

  belongs_to :book

  before_validation { self.due_on ||= Date.current + DEFAULT_DAYS }

  validates :borrower_name, presence: true, length: { maximum: 100 }
  validates :due_on, presence: true
  validate :due_on_within_limit, on: :create
  validate :copy_available, on: :create

  scope :active, -> { where(returned_at: nil) }
  scope :overdue, -> { active.where(due_on: ...Date.current) }
  scope :returned, -> { where.not(returned_at: nil) }

  def overdue? = returned_at.nil? && due_on < Date.current

  def as_json(*)
    { id:, book_id:, book_title: book.title, borrower_name:, due_on:, returned_at:, overdue: overdue? }
  end

  private

  def due_on_within_limit
    return if due_on.blank?

    errors.add(:due_on, "must be today or later") if due_on < Date.current
    errors.add(:due_on, "must be within #{MAX_DAYS} days") if due_on > Date.current + MAX_DAYS
  end

  def copy_available
    errors.add(:book, "has no copies available") if book && book.available_copies <= 0
  end
end
