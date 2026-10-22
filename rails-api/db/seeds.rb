# Fictional books and borrowers. Idempotent: safe to run on every start.
books = [
  [ "The Quiet Harbor", "Mara Ellison", "9798000000014", 3 ],
  [ "Patterns of the Northern Sky", "Tobias Renn", "9798000000021", 2 ],
  [ "A Short History of Small Machines", "Ines Calder", "9798000000038", 1 ],
  [ "Field Notes on Rivers", "Owen Hale", "9798000000045", 4 ]
].map do |title, author, isbn, copies|
  Book.find_or_create_by!(isbn:) { |book| book.assign_attributes(title:, author:, copies:) }
end

if Loan.none?
  books[0].loans.create!(borrower_name: "Avery Stone")
  books[2].loans.create!(borrower_name: "Jordan Pike", due_on: Date.current + 3)
end
